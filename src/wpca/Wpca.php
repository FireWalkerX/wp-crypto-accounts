<?php

	namespace wpblockchainaccounts;

	require_once __DIR__."/../utils/CurlRequest.php";
	require_once __DIR__."/../utils/BlockchainWallet.php";

	use wpblockchainaccounts\CurlRequest;
	use wpblockchainaccounts\BlockchainWallet;
	use \Exception;

	/**
	 * Command line tool to handle scheduled withdrawals.
	 */
	class Wpca {

		private $url;
		private $key;
		private $wallet;
		private $mockApi;
		private $secondWalletPassword;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->key=NULL;
		}

		/**
		 * Set mock api.
		 */
		public function setMockApi($mockApi) {
			$this->mockApi=$mockApi;
		}

		/**
		 * Set url for backend.
		 */
		public function setUrl($url) {
			$this->url=$url;
		}

		/**
		 * Set key for backend.
		 */
		public function setKey($key) {
			$this->key=$key;
		}

		/**
		 * Set wallet id.
		 */
		public function setWalletId($id) {
			$this->walletId=$id;
		}

		/**
		 * Set wallet password.
		 */
		public function setWalletPassword($pw) {
			$this->walletPassword=$pw;
		}

		/**
		 * Set second wallet password.
		 */
		public function setSecondWalletPassword($pw) {
			$this->secondWalletPassword=$pw;
		}

		/**
		 * Fail.
		 */
		private function fail($message) {
			echo $message."\n";
			exit(1);
		}

		/**
		 * Get wallet.
		 */
		private function getWallet() {
			if (!$this->wallet) {
				if (!$this->walletId || !$this->walletPassword)
					throw new Exception("Missing wallet id/password");

				$this->wallet=new BlockchainWallet($this->walletId,$this->walletPassword);

				if ($this->secondWalletPassword)
					$this->wallet->setSecondPassword($this->secondWalletPassword);
			}

			return $this->wallet;
		}

		/**
		 * Check response.
		 */
		private function checkResponse($res) {
			if (!$res["ok"])
				$this->fail($res["message"]);
		}

		/**
		 * Process transaction.
		 */
		private function processTransaction($transactionId) {
			$wallet=$this->getWallet();

			$r=$this->createRequest("beginTransaction");
			$r->setParam("transactionId",$transactionId);
			$res=$r->exec();
			$this->checkResponse($res);

			if (!$res["withdrawAddress"])
				throw new Exception("no withdraw address");

			if (!$res["amount"])
				throw new Exception("no amount");

			$wallet->send($res["withdrawAddress"],$res["amount"]);

			$r=$this->createRequest("endTransaction");
			$r->setParam("transactionId",$transactionId);
			$res=$r->exec();
			$this->checkResponse($res);
		}

		/**
		 * Process.
		 */
		public function process() {
			$r=$this->createRequest("scheduled");
			$res=$r->exec();
			$this->checkResponse($res);

			foreach ($res["transactions"] as $transaction)
				$this->processTransaction($transaction["id"]);

			$this->log(sizeof($res["transactions"])." transaction(s) processed.");
		}

		/**
		 * Process continously.
		 */
		public function processInterval($secs) {
			$this->log("Starting processing loop with ".$secs." second(s) interval(s).");

			while (TRUE) {
				$this->process();
				sleep($secs);
			}
		}

		/**
		 * Status.
		 */
		public function status() {
			$r=$this->createRequest("scheduled");
			$r->exec();

			$res=$r->getResult();
			if (!$res["ok"])
				$this->fail($res["message"]);

			$total=0;
			foreach ($res["transactions"] as $transaction) {
				$total+=$transaction["amount"];
			}

			$this->log("Transaction queue has ".sizeof($res["transactions"])." transaction(s), ".
				"the total amount is ".$total." satoshi.");

			$r=$this->createRequest("ongoing");
			$r->exec();

			$res=$r->getResult();
			if (!$res["ok"])
				$this->fail($res["message"]);

			$total=0;
			foreach ($res["transactions"] as $transaction) {
				$total+=$transaction["amount"];
			}

			$this->log("There are ".sizeof($res["transactions"])." transaction(s) currently processing, ".
				$total." satoshi is being transmitted.");
		}

		/**
		 * Log a message.
		 */
		private function log($message) {
			echo date("Y-m-d H:i:s")." ".$message."\n";
		}

		/**
		 * Create request.
		 */
		private function createRequest($method) {
			if ($this->mockApi) {
				$r=new CurlRequest();
				$r->setMockHandler(array($this->mockApi,$method));
			}

			else
				$r=new CurlRequest($this->url."/".$method);

			if ($this->key)
				$r->setParam("key",$this->key);

			$r->setResultProcessing(CurlRequest::JSON);

			return $r;
		}
	}