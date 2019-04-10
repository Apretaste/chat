<?php

class Service
{
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
				$chat->sent = date_format((new DateTime($message->sent)), 'd/m/Y - h:i a');
				$chat->read = date('d/m/Y G:i', strtotime($message->read));
				$chat->readed = $message->readed;
				$chats[] = $chat;
			}

			$content =  [
				"messages" => $chats,
				"username" => $user->username,
				"myusername" => $request->person->username,
				"id" => $user->id,
				"online" => $user->online,
				'last' => date('d/m/Y G:i', strtotime($user->last_access))
			];

			$response->setTemplate("chat.ejs", $content);
			return;
		}

		// get the list of people chating with you
		$chats = Social::chatsOpen($request->person->id);

		$response->setTemplate("main.ejs", ["chats" => $chats]);
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
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _escribir(Request $request, Response $response)
	{
		$userTo = Utils::getPerson($request->input->data->id);
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
			WHERE active = 1
			AND online = 1
			AND blocked = 0
			AND email <> '{$request->email}'
			ORDER BY last_access DESC
			LIMIT 20");

		// error if no users online
		if(empty($users)) {

			$response->setResponseSubject("No hay usuarios conectados");
			$response->createFromText("No hay nadie conectado en este momento. Por favor vuelva a intentar mas tarde.");
		}

		// format users
		$online = [];
		$social = new Social();
		foreach($users as $u) {
			$profile = $social->prepareUserProfile($u);
			$profile->picture = $profile->picture ? $profile->picture_public : "/images/user.jpg";
			$online[] = $profile;
		}

		// add path to root folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get images for the web
		$images = [];
		if($request->environment == "web") {
			foreach ($online as $user) {
				$images[] = $user->picture_internal;
				if($user->country) $images[] = "$wwwroot/public/images/flags/".strtolower($user->country).".png";
			}
		}

		// send info to the view
		$response->setResponseSubject("Usuarios conectados");
		$response->setTemplate("online.tpl", ['users' => $online], $images);
	}
}
