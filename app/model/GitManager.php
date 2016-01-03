<?php

class GitManager extends Nette\Object
{

	public function getCurrentBranchName($path)
	{
		$headContent = file($path.'/.git/HEAD');
		$explodedHeadContent = explode("/", $headContent[0]);

		$branchName = trim($explodedHeadContent[2]);

		return $branchName;
	}

	public function isClean($path)
	{
		$status = $this->getDiffExitCode($path);
		if(empty($status))
		{
			return TRUE;
		}

		return FALSE;
	}

	public function isUpToDate($path)
	{
		$status = $this->getStatus($path);
		$status = implode(' ', $status);

		if(strpos($status, 'Your branch is behind') !== FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}

	private function getStatus($path)
	{
		chdir($path);
		exec('git status', $out);

		return $out;
	}

	private function getDiffExitCode($path)
	{
		chdir($path);
		exec('git diff --exit-code', $out);

		return $out;
	}
}

class GitException extends LogicException
{

}

class GitNotOnDeployBranchException extends GitException
{

}

class GitNotCleanException extends GitException
{

}

class GitNotUpToDateException extends GitException
{

}
