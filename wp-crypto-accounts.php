<?php

/*
Plugin Name: Crypto Accounts
Plugin URI: http://github.com/limikael/wp-crypto-accounts
Version: 0.0.1
*/

	require_once __DIR__."/src/plugin/CryptoAccountsPlugin.php";
	require_once __DIR__."/src/controller/ShortcodeController.php";
	require_once __DIR__."/src/controller/SettingsController.php";
	require_once __DIR__."/src/utils/WpUtil.php";
	require_once __DIR__."/src/model/Account.php";
	require_once __DIR__."/src/model/Transaction.php";

	use wpblockchainaccounts\WpUtil;
	use wpblockchainaccounts\CryptoAccountsPlugin;
	use wpblockchainaccounts\ShortcodeController;
	use wpblockchainaccounts\SettingsController;
	use wpblockchainaccounts\Account;
	use wpblockchainaccounts\Transaction;

	CryptoAccountsPlugin::init();
	ShortcodeController::init();

	if (is_admin()) {
		SettingsController::init();
	}

	// Get a reference to a user account.
	if (!function_exists("bca_user_account")) {
		function bca_user_account($user) {
			return Account::getUserAccount($user);
		}
	}

	// Get a reference to an entity account.
	if (!function_exists("bca_entity_account")) {
		function bca_entity_account($entity_type, $entity_id) {
			return Account::getEntityAccount($entity_type, $entity_id);
		}
	}

	// Make transaction.
	if (!function_exists("bca_make_transaction")) {
		function bca_make_transaction($denomination, $fromAccount, $toAccount, $amount, $message=NULL) {
			$t=new Transaction();
			$t->setFromAccount($fromAccount);
			$t->setToAccount($toAccount);
			$t->setAmount($denomination,$amount);
			$t->notice=$message;
			$t->perform();

			return $t->id;
		}
	}