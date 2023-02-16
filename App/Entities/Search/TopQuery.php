<?php
    namespace App\Entities\Search;

    use App\Entities\JSONer;

    class TopQuery extends JSONer {
        public string $text;

        public int $weight;

        public function __construct($text, $weight) {
            $this->text   = $text;
            $this->weight = $weight;
        }

        /**
         * @return array
         */
        public function toArray() {
            return [
                'text'   => $this->text,
                'weight' => $this->weight
            ];
        }

        /**
         * @param array $arr
         * @return TopQuery[]
         */
        public static function manyFromArray($arr) {
            return array_map(function ($value) {
                return new self(
                    $value['text'],
                    $value['weight']
                );
            }, $arr);
        }
    }
?>