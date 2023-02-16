<?php
    namespace App\Responses;

    use App\Entities\JSONer;

    class Response extends JSONer {
        const EMPTY_INPUT = 1;

        const INVALID_INPUT = 2;

        const UNSPECIFIED_INPUT = 3;

        const EMPTY_RESPONSE = 4;

        public bool $isOK = false;

        public ?int $errorCode = null;

        public ?string $message = null;

        protected function __construct() {}

        /**
         * @return array
         */
        public function toArray() {
            $arr = [
                'is_ok'   => $this->isOK,
                'message' => $this->message
            ];

            if ($this->errorCode) {
                $arr['error_code'] = $this->errorCode;
            }

            return $arr;
        }

        /**
         * @param int $errorCode
         * @return Response
         */
        public static function withError(int $errorCode): static {
            $r = new static();

            $r->errorCode = $errorCode;

            return $r;
        }

        /**
         * @return Response
         */
        public static function ok(): static {
            $r = new static();

            $r->isOK = true;

            return $r;
        }
    }
?>