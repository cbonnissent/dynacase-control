<?php

$WIFF_ROOT = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$include_dir = $WIFF_ROOT . DIRECTORY_SEPARATOR . 'include';
if (!is_dir($include_dir)) {
	printf("Error: could not find include directory '%s'.\n", $include_dir);
	exit(1);
}

$reallyRemove = false;
$me = array_shift($argv);
if (count($argv) >= 1 && $argv[0] == '--remove') {
	$reallyRemove = true;
}

set_include_path(get_include_path().PATH_SEPARATOR.$include_dir);

putenv('WIFF_ROOT='.$WIFF_ROOT);

require_once ('class/Class.WIFF.php');

function __autoload($class_name) {
	require_once 'class/Class.'.$class_name.'.php';
}

$wiff = WIFF::getInstance();
if( $wiff === false ) {
	printf("Error getting WIFF instance: %s\n", $wiff->errorMessage);
	exit(1);
}

printf("* Inspecting repository list...\n");
$repoList = $wiff->getRepoList();
if ($repoList === false) {
	printf("Error getting repositories list: %s\n", $wiff->errorMessage);
	exit(1);
}

// Store list of invalid repositories
$invalidRepoNameList = array();
foreach ($repoList as &$repo) {
	if (preg_match("/freedom-ecm\.org/i", $repo->host)) {
		printf("Found invalid repository '%s' with host '%s'.\n", $repo->name, $repo->host);
		$invalidRepoNameList []= $repo->name;
	}
}
unset($repo);
if (count($invalidRepoNameList) <= 0) {
	printf("Good! found no invalid repositories.\n");
	exit(0);
}
if (!$reallyRemove) {
	printf("\nRe-run with '--remove' argument to remove the invalid repositories.\n\n");
	exit(0);
}

// Deactivate invalid repos on contexts
$contextList = $wiff->getContextList();
if ($contextList === false) {
	printf("Error getting contexts list: %s\n", $wiff->errorMessage);
	exit(1);
}
foreach ($contextList as &$context) {
	$deactivateList = array();
	foreach ($context->repo as &$repo) {
		if (in_array($repo->name, $invalidRepoNameList)) {
			$deactivateList []= $repo->name;
		}
	}
	unset($repo);
	if (count($deactivateList) <= 0) {
		continue;
	}
	foreach ($deactivateList as $repoName) {
		printf("* Deactivating repository '%s' on context '%s'.\n", $repoName, $context->name);
		if($context->deactivateRepo($repoName) === false) {
			printf("Error deactivating repository '%s' on context '%s': %s\n", $repoName, $context->name, $context->errorMessage);
			exit(1);
		}
		printf("Done.\n");
	}
}
unset($context);

// Remove invalid repositories
foreach ($invalidRepoNameList as $repoName) {
	printf("* Deleting repository '%s'.\n", $repoName);
	if ($wiff->deleteRepo($repoName) === false) {
		printf("Error deleting repository '%s': %s\n", $repoName, $wiff->errorMessage);
		exit(1);
	}
	printf("Done.\n");
}

exit(0);