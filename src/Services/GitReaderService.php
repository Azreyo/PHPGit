<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Reads data from a bare git repository on the filesystem using
 * the git CLI. All methods return safe, structured PHP data –
 * no raw shell output is ever passed to the caller unescaped.
 */
class GitReaderService
{
    private string $repoPath;

    private const EXT_MAP = [
        'php' => 'PHP',
        'js' => 'JavaScript',
        'jsx' => 'JavaScript',
        'mjs' => 'JavaScript',
        'ts' => 'TypeScript',
        'tsx' => 'TypeScript',
        'py' => 'Python',
        'pyw' => 'Python',
        'rb' => 'Ruby',
        'java' => 'Java',
        'c' => 'C',
        'h' => 'C',
        'cpp' => 'C++',
        'cc' => 'C++',
        'cxx' => 'C++',
        'hpp' => 'C++',
        'cs' => 'C#',
        'go' => 'Go',
        'rs' => 'Rust',
        'swift' => 'Swift',
        'kt' => 'Kotlin',
        'kts' => 'Kotlin',
        'dart' => 'Dart',
        'scala' => 'Scala',
        'html' => 'HTML',
        'htm' => 'HTML',
        'css' => 'CSS',
        'scss' => 'SCSS',
        'sass' => 'SCSS',
        'less' => 'Less',
        'sh' => 'Shell',
        'bash' => 'Shell',
        'zsh' => 'Shell',
        'fish' => 'Shell',
        'ps1' => 'PowerShell',
        'r' => 'R',
        'lua' => 'Lua',
        'pl' => 'Perl',
        'exs' => 'Elixir',
        'erl' => 'Erlang',
        'hrl' => 'Erlang',
        'hs' => 'Haskell',
        'lhs' => 'Haskell',
        'ml' => 'OCaml',
        'mli' => 'OCaml',
        'sql' => 'SQL',
        'vue' => 'Vue',
        'svelte' => 'Svelte',
        'json' => 'JSON',
        'yaml' => 'YAML',
        'yml' => 'YAML',
        'toml' => 'TOML',
        'xml' => 'XML',
        'md' => 'Markdown',
        'mdx' => 'Markdown',
        'dockerfile' => 'Dockerfile',
        'tf' => 'HCL',
        'hcl' => 'HCL',
        'nix' => 'Nix',
        'ex' => 'Elixir',
        'clj' => 'Clojure',
        'cljs' => 'ClojureScript',
        'groovy' => 'Groovy',
        'gradle' => 'Groovy',
        'jl' => 'Julia',
        'nim' => 'Nim',
        'cr' => 'Crystal',
    ];

    public const LANG_COLORS = [
        'PHP' => '#4F5D95',
        'JavaScript' => '#F7DF1E',
        'TypeScript' => '#3178C6',
        'Python' => '#3776AB',
        'Ruby' => '#CC342D',
        'Java' => '#B07219',
        'C' => '#555555',
        'C++' => '#F34B7D',
        'C#' => '#178600',
        'Go' => '#00ADD8',
        'Rust' => '#DEA584',
        'Swift' => '#FA7343',
        'Kotlin' => '#A97BFF',
        'Dart' => '#00B4AB',
        'Scala' => '#DC322F',
        'HTML' => '#E34C26',
        'CSS' => '#264DE4',
        'SCSS' => '#C6538C',
        'Less' => '#1D365D',
        'Shell' => '#89E051',
        'PowerShell' => '#012456',
        'R' => '#198CE7',
        'Lua' => '#000080',
        'Perl' => '#0298C3',
        'Elixir' => '#6E4A7E',
        'Erlang' => '#B83998',
        'Haskell' => '#5E5086',
        'OCaml' => '#3BE133',
        'SQL' => '#E38C00',
        'Vue' => '#41B883',
        'Svelte' => '#FF3E00',
        'JSON' => '#292929',
        'YAML' => '#CB171E',
        'TOML' => '#9C4221',
        'XML' => '#0060AC',
        'Markdown' => '#083FA1',
        'Dockerfile' => '#384D54',
        'HCL' => '#844FBA',
        'Nix' => '#7E7EFF',
        'Groovy' => '#E69F56',
        'Julia' => '#A270BA',
        'Nim' => '#FFE953',
        'Crystal' => '#000100',
        'Clojure' => '#DB5855',
    ];

    public function __construct(string $repoPath)
    {
        $this->repoPath = rtrim($repoPath, '/');
    }

    public function isEmpty(): bool
    {
        $exitCode = 1;
        $result = $this->git('rev-parse HEAD 2>/dev/null', $exitCode);
        return $exitCode !== 0 || trim($result) === '';
    }

    /**
     * @return list<string>  branch names, default branch first
     */
    public function getBranches(string $defaultBranch = 'main'): array
    {
        $raw = $this->git("branch --format='%(refname:short)'");
        $branches = array_filter(array_map('trim', explode("\n", $raw)));

        // Put default branch first
        $sorted = [$defaultBranch];
        foreach ($branches as $b) {
            if ($b !== $defaultBranch) {
                $sorted[] = $b;
            }
        }
        return array_values(array_unique($sorted));
    }

    /**
     * Top-level tree entries for a given branch/ref.
     *
     * @return list<array{type: string, name: string, mode: string}>
     */
    public function getTopLevelTree(string $ref = 'HEAD'): array
    {
        $safeRef = escapeshellarg($ref);
        $raw = $this->git("ls-tree {$safeRef}");
        if (trim($raw) === '') {
            return [];
        }

        $entries = [];
        foreach (explode("\n", trim($raw)) as $line) {
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^(\d+)\s+(blob|tree)\s+([0-9a-f]+)\t(.+)$/', $line, $m)) {
                continue;
            }
            $entries[] = [
                'mode' => $m[1],
                'type' => $m[2],   // 'blob' = file, 'tree' = directory
                'hash' => $m[3],
                'name' => $m[4],
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'tree' ? -1 : 1;
            }
            return strcmp($a['name'], $b['name']);
        });

        return $entries;
    }

    /**
     * Build a map of path → last-commit-info by scanning recent log entries.
     * Returns entries keyed by top-level path (file or dir name).
     *
     * @return array<string, array{hash: string, short: string, subject: string, time: string, rel: string, author: string}>
     */
    public function getLastCommitPerEntry(string $ref = 'HEAD', int $logLimit = 500): array
    {
        $safeRef = escapeshellarg($ref);
        // %x00-separated: hash, abbreviated hash, subject, ISO8601 date, relative date, author
        $raw = $this->git(
            "log {$safeRef} -n {$logLimit} --name-only --format='%x00%H%x1f%h%x1f%s%x1f%ai%x1f%ar%x1f%an'"
        );

        $map = [];
        $current = null;

        foreach (explode("\n", $raw) as $line) {
            if (str_starts_with($line, "\x00")) {
                $parts = explode("\x1f", ltrim($line, "\x00"), 6);
                $current = [
                    'hash' => $parts[0],
                    'short' => $parts[1],
                    'subject' => $parts[2],
                    'time' => $parts[3],
                    'rel' => $parts[4],
                    'author' => $parts[5],
                ];
            } elseif ($current !== null && trim($line) !== '') {
                $topLevel = explode('/', $line, 2)[0];
                if (!isset($map[$topLevel])) {
                    $map[$topLevel] = $current;
                }
            }
        }

        return $map;
    }

    /**
     * Get the single latest commit on a ref.
     *
     * @return array{hash: string, short: string, subject: string, time: string, rel: string, author: string}|null
     */
    public function getLatestCommit(string $ref = 'HEAD'): ?array
    {
        $safeRef = escapeshellarg($ref);
        $raw = $this->git("log {$safeRef} -1 --format='%H%x1f%h%x1f%s%x1f%ai%x1f%ar%x1f%an'");
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $parts = explode("\x1f", $raw, 6);
        return [
            'hash' => $parts[0],
            'short' => $parts[1],
            'subject' => $parts[2],
            'time' => $parts[3],
            'rel' => $parts[4],
            'author' => $parts[5],
        ];
    }

    public function getCommitCount(string $ref = 'HEAD'): int
    {
        $safeRef = escapeshellarg($ref);
        $raw = $this->git("rev-list --count {$safeRef}");
        return max(0, (int)trim($raw));
    }

    public function getReadme(string $ref = 'HEAD'): ?string
    {
        $candidates = ['README.md', 'readme.md', 'README', 'readme', 'README.txt'];
        foreach ($candidates as $name) {
            $safeRef = escapeshellarg($ref);
            $safeName = escapeshellarg($name);
            $exitCode = 1;
            $content = $this->git("show {$safeRef}:{$safeName} 2>/dev/null", $exitCode);
            if ($exitCode === 0 && $content !== '') {
                return $content;
            }
        }
        return null;
    }

    /**
     * Analyse file sizes by extension to produce a language breakdown.
     *
     * @return list<array{lang: string, bytes: int, pct: float, color: string}>  sorted desc
     */
    public function getLanguageBreakdown(string $ref = 'HEAD'): array
    {
        // ls-tree -r -l gives: mode type hash size TAB name
        $safeRef = escapeshellarg($ref);
        $raw = $this->git("ls-tree -r -l {$safeRef}");
        if (trim($raw) === '') {
            return [];
        }

        $totals = [];   // lang → bytes
        $total = 0;

        foreach (explode("\n", trim($raw)) as $line) {
            if ($line === '') {
                continue;
            }
            // format: mode SP type SP hash SP size TAB name
            if (!preg_match('/^\d+\s+blob\s+[0-9a-f]+\s+(\d+)\t(.+)$/', $line, $m)) {
                continue;
            }
            $size = (int)$m[1];
            $filename = basename($m[2]);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Special case: Dockerfile (no extension)
            if ($ext === '' && strtolower($filename) === 'dockerfile') {
                $ext = 'dockerfile';
            }

            $lang = self::EXT_MAP[$ext] ?? null;
            if ($lang === null) {
                continue;
            }

            $totals[$lang] = ($totals[$lang] ?? 0) + $size;
            $total += $size;
        }

        if ($total === 0) {
            return [];
        }

        arsort($totals);

        $result = [];
        $accPct = 0.0;
        $count = 0;

        foreach ($totals as $lang => $bytes) {
            $pct = round(($bytes / $total) * 100, 1);
            if ($pct < 0.1) {
                break;
            }
            $color = self::LANG_COLORS[$lang] ?? '#6c757d';

            // Collapse anything past 5 languages into "Other"
            if ($count >= 5) {
                // accumulate into Other
                $existingOther = null;
                foreach ($result as &$r) {
                    if ($r['lang'] === 'Other') {
                        $r['bytes'] += $bytes;
                        $existingOther = true;
                        break;
                    }
                }
                unset($r);
                if ($existingOther === null) {
                    $result[] = ['lang' => 'Other', 'bytes' => $bytes, 'pct' => 0.0, 'color' => '#6c757d'];
                }
                continue;
            }

            $result[] = ['lang' => $lang, 'bytes' => $bytes, 'pct' => $pct, 'color' => $color];
            $accPct += $pct;
            $count++;
        }

        // Recalculate percentages to ensure they add up to 100
        $newTotal = array_sum(array_column($result, 'bytes'));
        foreach ($result as &$r) {
            $r['pct'] = $newTotal > 0 ? round(($r['bytes'] / $newTotal) * 100, 1) : 0.0;
        }
        unset($r);

        return $result;
    }

    public function getPrimaryLanguage(string $ref = 'HEAD'): ?string
    {
        $breakdown = $this->getLanguageBreakdown($ref);
        return $breakdown[0]['lang'] ?? null;
    }


    private function git(string $subCommand, int &$exitCode = 0): string
    {
        $safePath = escapeshellarg($this->repoPath);
        $cmd = "git -C {$safePath} {$subCommand} 2>/dev/null";
        $output = [];
        exec($cmd, $output, $exitCode);
        return implode("\n", $output);
    }
}





