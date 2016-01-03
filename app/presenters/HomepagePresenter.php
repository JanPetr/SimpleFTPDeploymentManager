<?php

class HomepagePresenter extends BasePresenter
{
	/** @var DeploymentManager */
	protected $deploymentManager;

	private $dirToDeploy;

	public function renderDefault()
	{
		$dds = $this->deploymentManager->getDeployableDirs();
		$this->template->deployableDirs = $dds;
	}

	public function actionDeploy($dirToDeploy, $mode)
	{
		$this->setView('default');

		if($mode !== 'test' && $mode !== 'real')
		{
			$this->flashMessage('<strong>Wrong mode!</strong> Possible deploy modes are "test" or "real".', 'error');

			return;
		}

		try
		{
			if($mode === 'test')
			{
				$result = $this->deploymentManager->testDeploy($dirToDeploy);
			}
			else
			{
				$result = $this->deploymentManager->realDeploy($dirToDeploy);
			}

			$this->template->result = $result;
			$this->template->dirToDeploy = $dirToDeploy;

		}
		catch(DeploymentNotFoundDirectoryException $e)
		{
			$this->flashMessage('<strong>Wrong directory!</strong> Project "'.$dirToDeploy.'" is not set for automatic deploy so it cannot be deployed,', 'error');
		}
		catch(GitNotOnDeployBranchException $e)
		{
			$this->flashMessage('<strong>Wrong branch!</strong> Project "'.$dirToDeploy.'" is not on deployable branch so it cannot be deployed.', 'error');
		}
		catch(GitNotCleanException $e)
		{
			$this->flashMessage('<strong>Uncommited changes!</strong> Project "'.$dirToDeploy.'" has uncommited changes so it cannot be deployed.', 'error');
		}
		catch(GitNotUpToDateException $e)
		{
			$this->flashMessage('<strong>New commits on server!</strong> Project "'.$dirToDeploy.'" has new commits on server so it cannot be deployed.', 'error');
		}

		$this->dirToDeploy = $dirToDeploy;
	}

	protected function createComponentRealDeploy()
	{
		$form = new Nette\Application\UI\Form();

		$form->addHidden('dirToDeploy', $this->dirToDeploy);

		$form->addSubmit('realDeploy', 'Real deploy of "'.$this->dirToDeploy.'"')
		     ->setAttribute('class', 'btn btn-danger fixed');

		$form->onSuccess[] = callback($this, 'processRealDeploy');

		return $form;
	}

	public function processRealDeploy(Nette\Application\UI\Form $form)
	{
		$data = $form->values;

		$this->actionDeploy($data->dirToDeploy, 'real');
		$this->flashMessage('<strong>Deployed!</strong> Deploy was succesfully finished.', 'success');
	}

	/**
	 * @param DeploymentManager
	 */
	public function injectDeploymentManager(DeploymentManager $deploymentManager)
	{
		if($this->deploymentManager)
		{
			throw new Nette\InvalidStateException('DeploymentManager has already been set');
		}

		$this->deploymentManager = $deploymentManager;
	}

}
