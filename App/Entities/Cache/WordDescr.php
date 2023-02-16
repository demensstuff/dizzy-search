<?php
    namespace App\Entities\Cache;

    class WordDescr {
        public string $word;

        public int $offset;

        public bool $isIgnored;

        public function __construct(string $word, int $offset, bool $isIgnored) {
            $this->word      = $word;
            $this->offset    = $offset;
            $this->isIgnored = $isIgnored;
        }
    }
?>