<?php
class AsanaAPI {
	private $accessToken;
	private $fromProjectId;

	public function __construct($accessToken, $projectId)
	{
		$this->accessToken = $accessToken;
		$this->fromProjectId = $projectId;
	}

	private function asanaRequest($methodPath, $httpMethod = 'GET', $body = null)
	{
		$url = "https://app.asana.com/api/1.0/$methodPath";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
		if ($body)
		{
			if (!is_string($body))
			{
				$body = json_encode($body);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->accessToken,
				'Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		} else {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->accessToken));
		}

		$data = curl_exec($ch);

		curl_close($ch);

		$result = json_decode($data, true);
		return $result;
	}

	/**
	 * This creates the new task on the given Workspace in its specific Project.
	 *
	 * @param $workspaceId int Workspace ID to be transferred to.
	 * @param $projectId int Project ID to be transferred to.
	 * @param $task array Contains the task to be created with these indices;
	 * @param $parentTaskId int Parent task where this task will be part of.
	 * @return mixed
	 */
	private function createTask($workspaceId, $projectId, $task, $parentTask)
	{
		// If parent task is specified
		if ($parentTask !== null) {
			$result = $this->asanaRequest("tasks/${parentTask['id']}/subtasks", 'POST', $data);

			return $result;
		} else {
			$data = array('data' => $task);

			// Creates the task on the new workspace
			$result = $this->asanaRequest("workspaces/$workspaceId/tasks", 'POST', $data);

			if ($result['data']) {
				$newTask = $result['data'];
				$newTaskId = $newTask['id'];
				$data = array('data' => array('project' => $projectId));

				// Add the created task to the specific project.
				$result = $this->asanaRequest("tasks/$newTaskId/addProject", 'POST', $data);
				return $newTask;
			}

			return $result;
		}
	}

	private function copyTasks($workspaceId, $toProjectId, $parentTask = null)
	{
		$result = $this->asanaRequest("projects/$this->fromProjectId/tasks?opt_pretty&opt_fields=name,due_on,assignee_status,notes,assignee");
		$tasks = $result['data'];

		for ($i = count($tasks) - 1; $i >= 0; $i--) {
			$task = $tasks[$i];
			$newTask = $task;
			$originalTaskId = $newTask['id'];
			unset($newTask['id']);
			$newTask['assignee'] = $newTask['assignee']['id'];

			// Cleaning up empty values
			foreach ($newTask as $key => $value) {
				if (empty($value)) {
					unset($newTask[$key]);
				}
			}

			$newTask = $this->createTask($workspaceId, $toProjectId, $newTask, $parentTask);

			if ($newTask['id']) {
				$taskId = $task['id'];
				// Gets all the stories (comments, activites, etc. done to this task.)
				$result = $this->asanaRequest("tasks/$taskId/stories");
				$comments = array();

				foreach ($result['data'] as $story) {
					$date = date('l M d, Y h:i A', strtotime($story['created_at']));
					$comment = " ­\n" . $story['created_by']['name'] . ' on ' . $date . ":\n" . $story['text'];
					$comments[] = $comment;
				}

				$comment = implode("\n----------------------", $comments);
				$data = array('data' => array('text' => $comment));
				$newTaskId = $newTask['id'];
				// Stamps all the activites in the new task under the comment section.
				$result = $this->asanaRequest("tasks/$originalTaskId/stories", 'POST', $data);
			}

			$childTasks = $this->asanaRequest("tasks/$originalTaskId/subtasks")['data'];

			// Recurse here
			if ($childTasks) {
				for ($j = 0; $j < count($childTask); $j++) {
					$this->copyTasks($workspaceId, $toProjectId, $childTasks[$j]);
				}
			}

		}
	}

	public function copyProjectTo($toProjectId)
	{
		$result = $this->asanaRequest("projects/$toProjectId");

		if (!$result['data']) {
			return;
		}

		$workspaceId = $result['data']['workspace']['id'];
		$this->copyTasks($toProjectId, $workspaceId);
	}
}
