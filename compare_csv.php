<?php

declare(strict_types=1);

class CsvComparison
{
    private string $dir1;
    private string $dir2;
    private array $differences = [];

    public function __construct(string $dir1, string $dir2)
    {
        // Construct full paths under ./directories
        $this->dir1 = './directories/' . rtrim($dir1, '/');
        $this->dir2 = './directories/' . rtrim($dir2, '/');

        if (!is_dir($this->dir1)) {
            throw new RuntimeException("First directory not found: {$this->dir1}");
        }

        if (!is_dir($this->dir2)) {
            throw new RuntimeException("Second directory not found: {$this->dir2}");
        }
    }

    public function compare(): void
    {
        $this->compareDirectories($this->dir1, $this->dir2);
        $this->displayResults();
    }

    private function compareDirectories(string $path1, string $path2): void
    {
        $items1 = scandir($path1);
        $items2 = scandir($path2);

        foreach ($items1 as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath1 = $path1 . '/' . $item;
            $fullPath2 = $path2 . '/' . $item;

            if (!file_exists($fullPath2)) {
                $this->differences[] = "File missing in second directory: {$fullPath2}";
                continue;
            }

            if (is_dir($fullPath1)) {
                $this->compareDirectories($fullPath1, $fullPath2);
            } elseif (str_ends_with(strtolower($item), '.csv')) {
                $this->compareCsvFiles($fullPath1, $fullPath2);
            }
        }

        // Check for files that exist in dir2 but not in dir1
        foreach ($items2 as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath1 = $path1 . '/' . $item;
            if (!file_exists($fullPath1)) {
                $this->differences[] = "File missing in first directory: {$fullPath1}";
            }
        }
    }

    private function compareCsvFiles(string $file1, string $file2): void
    {
        $content1 = $this->readCsvFile($file1);
        $content2 = $this->readCsvFile($file2);

        if (count($content1) !== count($content2)) {
            $this->differences[] = sprintf(
                "Row count mismatch in %s: %d vs %d rows",
                basename($file1),
                count($content1),
                count($content2)
            );
            return;
        }

        foreach ($content1 as $index => $row1) {
            if (!isset($content2[$index])) {
                $this->differences[] = "Missing row at index {$index} in {$file2}";
                continue;
            }

            $row2 = $content2[$index];

            if (count($row1) !== count($row2)) {
                $this->differences[] = sprintf(
                    "Column count mismatch at row %d in %s: %d vs %d columns",
                    $index + 1,
                    basename($file1),
                    count($row1),
                    count($row2)
                );
                continue;
            }

            foreach ($row1 as $colIndex => $value1) {
                if (!isset($row2[$colIndex])) {
                    $this->differences[] = "Missing column at index {$colIndex} in row {$index} of {$file2}";
                    continue;
                }

                $value2 = $row2[$colIndex];
                if ($this->normalizeValue($value1) !== $this->normalizeValue($value2)) {
                    $this->differences[] = sprintf(
                        "Value mismatch in %s, row %d, column %d: '%s' vs '%s'",
                        basename($file1),
                        $index + 1,
                        $colIndex + 1,
                        $value1,
                        $value2
                    );
                }
            }
        }
    }

    private function readCsvFile(string $file): array
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open file: {$file}");
        }

        $data = [];
        while (($row = fgetcsv($handle, null, ",", "\"", "\\")) !== false) {
            $data[] = $row;
        }

        fclose($handle);
        return $data;
    }

    private function normalizeValue(string $value): string
    {
        // Normalize values to handle potential formatting differences
        // Trim whitespace, convert to lowercase for case-insensitive comparison
        return trim(strtolower($value));
    }

    private function displayResults(): void
    {
        if (empty($this->differences)) {
            echo "✅ All CSV files are identical between the two directories.\n";
            return;
        }

        echo "❌ Found " . count($this->differences) . " differences:\n\n";
        foreach ($this->differences as $difference) {
            echo "- {$difference}\n";
        }
    }
}

// Check for command line arguments
if ($argc !== 3) {
    echo "Usage: php compare_csv.php <dir1> <dir2>\n";
    echo "Example: php compare_csv.php dir1 dir2\n";
    exit(1);
}

// Get directory names from command line arguments
$dir1 = $argv[1];
$dir2 = $argv[2];

try {
    $comparison = new CsvComparison($dir1, $dir2);
    $comparison->compare();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}