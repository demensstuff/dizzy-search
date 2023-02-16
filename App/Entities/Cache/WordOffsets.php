<?php
    namespace App\Entities\Cache;

    use App\Entities\JSONer;

    class WordOffsets {
        public int $start;

        public int $end;

        public string $word;

        public int $exactPos;

        public ?int $tokenizedPos;

        public function __construct(
            int $start,
            int $end,
            string $word,
            int $exactPos,
            ?int $tokenizedPos
        ) {
            $this->start        = $start;
            $this->end          = $end;
            $this->word         = $word;
            $this->exactPos     = $exactPos;
            $this->tokenizedPos = $tokenizedPos;
        }

        /**
         * @return array
         */
        public function toArray() {
            return [
                'start'         => $this->start,
                'end'           => $this->end,
                'word'          => $this->word,
                'exact_pos'     => $this->exactPos,
                'tokenized_pos' => $this->tokenizedPos
            ];
        }

        /**
         * @param array $arr
         * @return WordOffsets[]
         */
        public static function manyFromArray($arr) {
            return array_map(function ($value) {
                return new self(
                    $value['start'],
                    $value['end'],
                    $value['word'],
                    $value['exact_pos'],
                    $value['tokenized_pos']
                );
            }, $arr);
        }
    }
?>