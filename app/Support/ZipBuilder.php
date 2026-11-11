<?php

namespace App\Support;

/**
 * Penulis berkas ZIP sederhana tanpa ekstensi zip.
 *
 * Dipakai untuk membuat berkas .xlsx dan .docx yang merupakan paket ZIP.
 * Semua berkas disimpan tanpa kompresi (store) agar tidak membutuhkan
 * ekstensi ZIP maupun library tambahan.
 */
class ZipBuilder
{
    private string $contents = '';

    /**
     * @var array<int, array{name: string, crc: int, size: int, offset: int, time: int, date: int}>
     */
    private array $entries = [];

    public function addFile(string $name, string $data): void
    {
        $name = str_replace('\\', '/', $name);
        [$time, $date] = $this->dosTimestamp();
        $crc = crc32($data);
        $size = strlen($data);
        $offset = strlen($this->contents);

        $this->contents .= pack(
            'VvvvvvVVVvv',
            0x04034b50,
            20,
            0,
            0,
            $time,
            $date,
            $crc,
            $size,
            $size,
            strlen($name),
            0
        ).$name.$data;

        $this->entries[] = [
            'name' => $name,
            'crc' => $crc,
            'size' => $size,
            'offset' => $offset,
            'time' => $time,
            'date' => $date,
        ];
    }

    public function getContents(): string
    {
        $centralOffset = strlen($this->contents);
        $central = '';

        foreach ($this->entries as $entry) {
            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $entry['time'],
                $entry['date'],
                $entry['crc'],
                $entry['size'],
                $entry['size'],
                strlen($entry['name']),
                0,
                0,
                0,
                0,
                0,
                $entry['offset']
            ).$entry['name'];
        }

        $count = count($this->entries);

        return $this->contents.$central.pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            strlen($central),
            $centralOffset,
            0
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dosTimestamp(): array
    {
        $now = getdate();
        $time = ($now['hours'] << 11) | ($now['minutes'] << 5) | intdiv($now['seconds'], 2);
        $date = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        return [$time, $date];
    }
}
