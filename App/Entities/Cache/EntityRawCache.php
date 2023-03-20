<?php

namespace App\Entities\Cache;

/** This class contains a raw cache */
class EntityRawCache {
    /** @var array The array of offsets (word position => word offset => word offset end
     * in the raw cache
     */
    protected array $arr = [];

    /** @var int Number of words in cache */
    protected int $num = 0;

    /** @var string Raw file text with cleaned whitespaces */
    protected string $rawText = '';

    public function __construct(?array $arr = null) {
        $this->arr     = $arr['offsets'] ?? [];
        $this->rawText = $arr['raw_text'] ?? '';
        $this->num     = count($this->arr);
    }

    /**
     * @param int $pos Word number
     * @param int $offset Word start offset in the raw cache
     * @param int $offsetEnd Word end offset in the raw cache
     * @return void
     */
    public function add(int $pos, int $offset, int $offsetEnd): void {
        $this->arr[$pos] = [ $offset, $offsetEnd ];
        $this->num++;
    }

    /**
     * @param string $rawText Raw text to be set
     * @return void
     */
    public function setRawText(string $rawText): void {
        $this->rawText = $rawText;
    }

    /**
     * @return array Raw cache as an array
     */
    public function toArray(): array {
        return [
            'offsets'  => $this->arr,
            'raw_text' => $this->rawText
        ];
    }

    /**
     * @return int Number of words in cache
     */
    public function num(): int {
        return $this->num;
    }

    /**
     * @param int $pos Word position
     * @return ?int Word start offset
     */
    public function getStart(int $pos): ?int {
        return $this->arr[$pos][0] ?? null;
    }

    /**
     * @param int $pos Word position
     * @return ?int Word end offset
     */
    public function getEnd(int $pos): ?int {
        return $this->arr[$pos][1] ?? null;
    }

    /**
     * @return string Raw text
     */
    public function getRawText(): string {
        return $this->rawText;
    }
}