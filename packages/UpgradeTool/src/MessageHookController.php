<?php

namespace Packages\UpgradeTool;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use XinYin\UpgradeTool\Helper\CommonHelper;

class MessageHookController extends Controller
{
    /** @var string 環境名稱 */
    private $env_name;

    /** @var string 可辨識符號 */
    private $symbol;

    protected array $settings = [];

    protected $symbols = [];

    public function __construct(string $env)
    {
        $this->settings = CommonHelper::getEnvSettings();
        $this->symbols = collect($this->settings)
            ->reject(fn (array $setting) => is_null($setting['symbol']) || $setting['symbol'] === '')
            ->pluck('symbol');

        $setting = Arr::get($this->settings, $env);

        $this->symbol = $setting['symbol'];
        $this->env_name = $setting['name'];
    }

    /**
     * 更新環境
     *
     * @return void
     */
    public function updateEnv()
    {
        $path = base_path();
        // Change to base directory
        chdir($path);

        // 更新tag
        shell_exec('git fetch -t -f');

        $output = shell_exec('git for-each-ref --sort=creatordate --format="%(refname) %(objectname)" refs/tags');

        // 取得最新的tag
        $tags = collect(explode("\n", $output))
            ->filter()
            ->map(fn ($tag) => $this->formatTag($tag))
            ->filter(fn ($tag) => $this->isNeedsTag($tag['version']))
            ->reverse('version')
            ->unique('commit');

        $message = "準備更新{$this->env_name}環境, 沒有對應標籤";

        if ($tags->isNotEmpty()) {
            $latest_version = $this->pluckLatestVersion($tags);

            $commits = $this->getCommitMessage($tags);

            $updated_commits = $commits
                            ->pipe($this->classification(...))
                            ->pipe($this->buildComment(...));

            $message = "<users/all> 準備更新{$this->env_name}環境 \n版號: {$latest_version} \n更新內容：\n{$updated_commits} \n";

            CommonHelper::sendWebHook($message, CommonHelper::getThreadKey($latest_version));
        } else {
            CommonHelper::sendWebHook($message, CommonHelper::getThreadKey(''));
        }
    }

    /**
     * 分類 commit
     * @param Collection $commits
     *
     * @return Collection
     */
    private function classification(Collection $commits): Collection
    {
        $key = 0;

        $result = collect();

        $commits->filter()->groupBy(function ($commit) use (&$key) {
            if (strpos($commit, 'Merge branch') !== false) {
                $key += 1;
            }

            return $key;
        })->each(function ($group) use (&$result) {
            $type = 'undefined';
            $merge_commit = $group->first();

            if (strpos($merge_commit, 'Merge branch') !== false) {
                $branch = explode("'", $merge_commit)[1];
                $type = explode('/', $branch)[0];
            }

            if(! isset($result[$type])) {
                $result[$type] = collect();
            }

            $group->reject(fn ($item, $key) => strpos($item, 'Merge branch') !== false)
                ->each(fn ($item) => $result[$type]->push(mb_substr(string: $item, start: 9, encoding: 'utf8')));
        });

        $group_order = ['undefined', 'hotfix', 'feature', 'bug'];

        return $result->sortBy(fn ($group, $key) => array_keys($group_order, $key));
    }

    /**
     * 建立訊息
     *
     * @param Collection $grouped_commits
     *
     * @return string
     */
    private function buildComment(Collection $grouped_commits): string
    {
        $comment = "";

        $grouped_commits->each(function ($commits, $type) use (&$comment) {
            $type = strtoupper($type);
            $comment .= "\n `$type` : \n";

            $comment .= $commits->join("\n");
            $comment .= "\n";
        });

        return $comment;
    }

    /**
     * 獲得commit訊息
     *
     * @param Collection $tags
     *
     * @return Collection
     */
    protected function getCommitMessage(Collection $tags): Collection
    {
        $real_tags = $tags->take(2);
        $new_tag = $real_tags->first();
        $previous_tag = $real_tags->last();

        return collect(explode("\n", $this->getDiffCommit($new_tag['commit'], $previous_tag['commit'])));
    }

    /**
     * 獲得最新版本號
     *
     * @param Collection $tags
     *
     * @return string
     */
    protected function pluckLatestVersion(Collection $tags): string
    {
        return $tags->first()['version'];
    }

    /**
     * 格式化tag內的資料
     *
     * @param string $tag
     *
     * @return array
     */
    protected function formatTag(string $tag): array
    {
        $tag = str_replace('refs/tags/', '', $tag);

        $tag = substr($tag, 0, -32);

        [$version, $commit] = explode(' ', $tag);

        return [
            'version' => $version,
            'commit'  => $commit
        ];
    }

    /**
     * 獲得最新版本與上一版本之間的差異
     *
     * @param string $last_commit
     * @param string $previous_commit
     *
     * @return string
     */
    protected function getDiffCommit(string $last_commit, string $previous_commit): string
    {
        if ($last_commit === $previous_commit) {
            return shell_exec("git log {$last_commit} --oneline");
        }

        return shell_exec("git log {$last_commit}...{$previous_commit} --oneline");
    }

    /**
     * 篩選版本是否為需要部署的環境的tag
     *
     * @param string $version
     *
     * @return bool
     */
    protected function isNeedsTag(string $version): bool
    {
        if (is_null($this->symbol) || $this->symbol === '') {
            return $this->symbols->filter(fn (string $symbol) => strpos($version, $symbol))->isEmpty();
        } else {
            return ! (strpos($version, $this->symbol) === false);
        }
    }
}
