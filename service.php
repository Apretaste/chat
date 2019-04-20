<?php

class Service
{
	private $provinces = [
		'PINAR_DEL_RIO'=>'Pinar del Rio','LA_HABANA'=>'La Habana','ARTEMISA'=>'Artemisa','MAYABEQUE'=>'Mayabeque',
		'MATANZAS'=>'Matanzas','VILLA_CLARA'=>'Villa Clara','CIENFUEGOS'=>'Cienfuegos','SANCTI_SPIRITUS'=>'Sancti Spiritus',
		'CIEGO_DE_AVILA'=>'Ciego de Avila','CAMAGUEY'=>'Camaguey','LAS_TUNAS'=>'Las Tunas','HOLGUIN'=>'Holguin',
		'GRANMA'=>'Granma','SANTIAGO_DE_CUBA'=>'Santiago de Cuba','GUANTANAMO'=>'Guantanamo','ISLA_DE_LA_JUVENTUD'=>'Isla de la Juventud', ''=>''
	];
	/**
	 * Get the list of conversations, or post a note
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _main(Request $request, Response $response)
	{
		if(isset($request->input->data->userId)){
			// get the username of the note
			$user = Utils::getPerson($request->input->data->userId);

			// check if the username is valid
			if(!$user){
				$response->setTemplate("notFound.ejs");
				return;
			}

			$messages = Social::chatConversation($request->person->id, $user->id);
			
			$chats = [];

			foreach ($messages as $message) {
				$chat = new stdClass();
				$chat->id = $message->note_id;
				$chat->username = $message->username;
				$chat->text = $message->text;
				$chat->sent = date_format((new DateTime($message->sent)), 'd/m/Y h:i a');
				$chat->read = date('d/m/Y h:i a', strtotime($message->read));
				$chat->readed = $message->readed;
				$chats[] = $chat;
			}

			$content =  [
				"messages" => $chats,
				"username" => $user->username,
				"myusername" => $request->person->username,
				"id" => $user->id,
				"online" => $user->online,
				'last' => date('d/m/Y h:i a', strtotime($user->last_access))
			];

			$response->setTemplate("chat.ejs", $content);
			return;
		}

		// get the list of people chating with you
		$chats = Social::chatsOpen($request->person->id);

		$response->setTemplate("main.ejs", ["chats" => $chats, "myusername" => $request->person->username]);
	}

	/**
	 *
	 *@author ricardo@apretaste.org
	 *@param Request
	 *@param Response
	 */
	 public function _borrar(Request $request, Response $response){
		 $deleteType = $request->input->data->type;
		 $idToHide = $request->input->data->id;
		 
		 if($deleteType == "chat") Social::chatHide($request->person->id, $idToHide);
		 if($deleteType == "message") Social::chatMessageHide($request->person->id, $idToHide);
	 }

	/**
	 * Search an user by username
	 * 
	 * @author ricardo@apretaste.org
	 * @param Request $request
	 * @param Response $response
	 */

	public function _buscar(Request $request, Response $response)
	{
		$username = $request->input->data->username;
		$user = Utils::getPerson($username);
		if(!$user){
			$response->setTemplate('notFound.ejs');
			return;
		}

		$request->input->data->userId = $user->id;
		$this->_main($request, $response);
	}

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _escribir(Request $request, Response $response)
	{
		if(!isset($request->input->data->id)) return
		$userTo = Utils::getPerson($request->input->data->id);
		if(!$userTo) return;
		$message = $request->input->data->message;

		$blocks = Social::isBlocked($request->person->id ,$userTo->id);
		if ($blocks->blocked>0 || $blocks->blockedByMe>0){
			Utils::addNotification(
				$request->person->id, 
				"Su mensaje para @{$userTo->username} no pudo ser entregado, es posible que usted haya sido bloqueado por esa persona.", 
				"{'command':'PERFIL', 'data':{'id':'{$userTo->id}'}",
				'error'
			);
			return;
		}
		
		// store the note in the database
		$message = Connection::escape($message, 499);
		Connection::query("INSERT INTO _note (from_user, to_user, `text`) VALUES ({$request->person->id},{$userTo->id},'$message')");

		// send notification for the app
		Utils::addNotification(
			$userTo->id,
			"@{$request->person->username} le ha enviado una nota",
			"{'command':'CHAT', 'data':{'id':'{$request->person->id}'}}",
			'message'
		);
	}

	/**
	 * Sub-service ONLINE
	 *
	 * @param \Request $request
	 * @return \Response
	 */
	public function _online(Request $request, Response $response)
	{
		// get online users
		$connection = new Connection();
		$users = $connection->query("
			SELECT *
			FROM person
			WHERE online = 1
			AND blocked = 0
			AND id <> '{$request->person->id}'
			ORDER BY last_access DESC
			LIMIT 20");

		// format users
		$online = [];
		foreach($users as $user) {
			$profile = Social::prepareUserProfile($user);
			$online[] = [
				'id' => $profile->id,
				'username' => $profile->username,
				'age' => $profile->age,
				'province' => $this->provinces[$profile->province],
				'gender' => $profile->gender
			];
		}

		// send info to the view
		$response->setCache(5);
		$response->setTemplate("online.ejs", ['users' => $online]);
	}
}
