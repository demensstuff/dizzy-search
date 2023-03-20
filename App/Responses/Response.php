<?php 
	namespace App\Responses;

    /** This class represents a response */
	class Response {
        /** @var int Error code indicating that the input is empty */
		const EMPTY_INPUT = 1;

        /** @var int Error code indicating that the input is invalid */
		const INVALID_INPUT = 2;

        /** @var int Error code indicating the input is valid, but can't be processed */
		const UNSPECIFIED_INPUT = 3;

        /** @var int Error code indicating that the response is empty */
		const EMPTY_RESPONSE = 4;

        /** @var bool Whether this is the successful response */
		public bool $isOK = false;

        /** @var ?int Response error code */
		public ?int $errorCode = null;

        /** @var ?string Custom response error message */
		public ?string $message = null;
		
		protected function __construct() {}
		
		/**
         * Indicated an erroneous operation
		 * @param int $errorCode One of the class constants
		 * @return Response
		 */
		public static function withError(int $errorCode): static {
			$r = new static();
			
			$r->errorCode = $errorCode;
			
			return $r;
		}
		
		/**
         * Indicates a successful operation
		 * @return Response
		 */
		public static function ok(): static {
			$r = new static();
			
			$r->isOK = true;
			
			return $r;
		}

        /**
         * Converts the object to the array
         * @return array string $key => mixed $value
         */
        public function toArray(): array {
            $arr = [
                'is_ok'   => $this->isOK,
                'message' => $this->message
            ];

            if ($this->errorCode) {
                $arr['error_code'] = $this->errorCode;
            }

            return $arr;
        }
	}