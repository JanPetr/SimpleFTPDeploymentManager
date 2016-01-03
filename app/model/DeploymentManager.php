<?php

class DeploymentManager extends Nette\Object
{
	private $rootDir;
	private $deploymentDir;
	private $deploymentConfig;

	private $gitManager;

	public function __construct($rootDir, $deploymentDir, $deploymentConfig, GitManager $gitManager)
	{
		$this->rootDir = $rootDir;
		$this->deploymentDir = $deploymentDir;
		$this->deploymentConfig = $deploymentConfig;

		$this->gitManager = $gitManager;
	}

	public function getDeployableDirs()
	{
		$deployableDirs = array();

		$dirs = dir($this->rootDir);
		while($f = $dirs->read())
		{
			if(is_dir($this->rootDir.$f))
			{
				$scan = scandir($this->rootDir.$f);
				if(in_array('deploy_config.ini', $scan))
				{
					preg_match('/remote = (.*)/', file_get_contents($this->rootDir.$f.'/deploy_config.ini'), $regs);
					$deployableDirs[] = $f.'||'; //.$regs[1];
				}
			}
		}

		foreach($deployableDirs as &$dd)
		{
			$pieces = explode('||', $dd);
			$directoryName = $pieces[0];

			preg_match('/(ftp:\/\/.*):.*(@.*)/', $pieces[1], $regs);
			unset($regs[0]);
			$ftp = implode('', $regs);

			$path = $this->rootDir.$directoryName;

			$dd = array();
			$dd['directory'] = $directoryName;
			$dd['branch'] = $this->gitManager->getCurrentBranchName($path);
			$dd['isOnDeployBranch'] = ($this->gitManager->getCurrentBranchName($path) === $this->deploymentConfig['deployBranch']);
			$dd['isUpToDate'] = $this->deploymentConfig['allowOnlyUpToDate'] === TRUE ? $this->gitManager->isUpToDate($path) : TRUE;
			$dd['isClean'] = $this->deploymentConfig['allowOnlyClean'] === TRUE ? $this->gitManager->isClean($path) : TRUE;
			$dd['ftp'] = $ftp;
		}

		return $deployableDirs;
	}

	public function testDeploy($dirToDeploy)
	{
		return $this->deploy($dirToDeploy, ' -t');
	}

	public function realDeploy($dirToDeploy)
	{
		return $this->deploy($dirToDeploy);
	}

	private function deploy($dirToDeploy, $parameter = '')
	{
		$found = FALSE;
		$deployableDirs = $this->getDeployableDirs();
		foreach($deployableDirs as $dd)
		{
			if($dd['directory'] == $dirToDeploy)
			{
				$found = TRUE;
				if($dd['isOnDeployBranch'] === FALSE)
				{
					throw new GitNotOnDeployBranchException();
				}
				elseif($dd['isClean'] === FALSE)
				{
					throw new GitNotCleanException();
				}
				elseif($dd['isUpToDate'] === FALSE)
				{
					throw new GitNotUpToDateException();
				}
			}
		}

		if($found === FALSE)
		{
			throw new DeploymentNotFoundDirectoryException();
		}

		$dirToDeploy = $this->rootDir.$dirToDeploy;

		$configPath = $dirToDeploy.'/deploy_config.ini';

		if(file_exists($dirToDeploy.'/deploy_config.log'))
		{
			unlink($dirToDeploy.'/deploy_config.log');
		}

		$ignoredFiles = $this->getIgnoredFiles($dirToDeploy);
		$configContents = $tempConfigContents = file_get_contents($configPath);
		$configContents = str_replace('[here insert gitignore contents]', $ignoredFiles, $configContents);
		$configContents = str_replace($dirToDeploy, '', $configContents);

		file_put_contents($configPath, $configContents);

		$run = 'php '.$this->deploymentDir.'deployment.php "'.$configPath.'"'.$parameter;
		exec($run, $out);

		file_put_contents($configPath, $tempConfigContents);

		$logContent = file_get_contents($dirToDeploy.'/deploy_config.log');

		return $logContent;
	}

	private function getIgnoredFiles($path)
	{
		$toIgnoreFiles = '';
		foreach(Nette\Utils\Finder::findFiles('.gitignore')->from($path) as $giFile)
		{
			$filePath = str_replace('.gitignore', '', $giFile);
			$contents = explode("\n", trim(file_get_contents($giFile)));
			foreach($contents as $ignoredLine)
			{
				$ignoredLine = trim($ignoredLine);

				$prefix = '';
				if(mb_substr($ignoredLine, 0, 1) === '!')
				{
					$prefix = '!';
					$ignoredLine = mb_substr($ignoredLine, 1);
				}

				if($ignoredLine === '*')
				{
					$ignoredLine = mb_substr($ignoredLine, 0, -2);
				}

				$toIgnoreFiles .= $prefix.$filePath.$ignoredLine."\n";
			}
		}

		return $toIgnoreFiles;
	}
}

class DeploymentNotFoundDirectoryException extends RuntimeException
{
	//
}