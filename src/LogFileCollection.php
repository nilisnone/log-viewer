<?php

namespace Nilisnone\LogViewer;

use Illuminate\Support\Collection;
use Nilisnone\LogViewer\Readers\MultipleLogReader;

/**
 * @var LogFile[] $items
 */
class LogFileCollection extends Collection
{
    public function sortByEarliestFirst(): self
    {
        $this->items = $this->sortBy(function (LogFile $file) {
            return $file->earliestTimestamp().($file->name ?? '');
        }, SORT_NATURAL)->values()->toArray();

        return $this;
    }

    public function sortByLatestFirst(): self
    {
        $this->items = $this->sortByDesc(function (LogFile $file) {
            return $file->latestTimestamp().($file->name ?? '');
        }, SORT_NATURAL)->values()->toArray();

        return $this;
    }

    public function latest(): ?LogFile
    {
        return $this->sortByDesc->latestTimestamp()->first();
    }

    public function earliest(): ?LogFile
    {
        return $this->sortBy->earliestTimestamp()->first();
    }

    public function logs(): MultipleLogReader
    {
        return new MultipleLogReader($this->items);
    }
}
