<?php

class Chat extends Service
{
	/**
	 * Get the list of conversations, or post a note
	 *
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 */
	public function _main(Request $request)
	{
		//
		// SHOW THE LIST OF OPEN CHATS WHEN SUBJECT=NOTA
		//
		if(empty($request->query))
		{
			// get the list of people chating with you
			$notes = $this->social->chatsOpen($request->userId);
			// show home page if no notes found
			if(empty($notes)) {
				$response = new Response();
				$response->setResponseSubject("Lista de chats abiertos");
				$response->createFromTemplate("home.tpl", []);
				return $response;
			}

			// get images for the web
			$images = [];
			if($request->environment == "web") {
				foreach ($notes as $note) {
					$images[] = $note->profile->picture_internal;
				}
			}
			// send data to the view
			$response = new Response();
			$response->setResponseSubject("Lista de chats abiertos");
			$response->createFromTemplate("open.tpl", ["notes" => $notes], $images);
			return $response;
		}

		// get the username of the note
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$friendId = $this->utils->getIdFromUsername($friendUsername);

		// check if the username is valid
		if(empty($friendId)) {
			$response = new Response();
			$response->setResponseSubject("El usuario @$friendUsername no existe");
			$response->createFromText("El usuario @$friendUsername no existe en Apretaste, por favor compruebe que el @username es valido. Puede que halla cometido un error al escribirlo o que la persona halla cambiado su @username.");
			return $response;
		}

		//
		// GET A LIST OF THE CHATS WHEN SUBJECT=NOTA @USERNAME
		//
		if(count($argument) == 1)
		{
			return $this->_get($request, $friendId);
		}

		// get text of the the note to post
		unset($argument[0]);
		$note = implode(" ", $argument);

		//
		// POST A NOTE WHEN SUBJECT=NOTA @username MY NOTE HERE
		//
		return $this->_post($request, $friendUsername, $friendId, $note);
	}

	/**
	 * Get latest chats after certain NOTE_ID
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 */
	public function _get(Request $request, $friendId=false)
	{
		// get the username and ID of the query
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$lastID = isset($argument[1]) ? $argument[1] : 0;

		// get the friend email if not passed
		if(empty($friendId)) {
			$friendId = $this->utils->getIdFromUsername($friendUsername);
			if( ! $friendId) {
				$response = new Response();
				return $response->createFromJSON('{"code":"ERROR", "message":"Wrong username"}');
			}
		}

		// get the array of notes
		$notes = $this->social->chatConversation($request->userId, $friendId, $lastID);

		// get the new last ID and remove ID for each note
		$newLastID = 0;
		$chats = [];
		$friend=$this->utils->getPerson($friendId);
		foreach($notes as $nota) {
			if($nota->id > $newLastID) $newLastID = $nota->id; // for the piropazo app
			$chat = new stdClass();
			$chat->username = $nota->username;
			$chat->gender = $nota->gender;
			$chat->picture = $nota->picture_internal;
			$chat->text = $nota->text;
			$chat->sent = $nota->sent;
			$chat->read = date('d/m/Y G:i',strtotime($nota->read));
			$chat->readed = $nota->readed;
			$chats[] = $chat;
		}

		// prepare the details for the view
		$content = [
			"code" => "ok",
			"last_id" => $newLastID,
			"friendUsername" => $friendUsername,
			"online" => $friend->online,
			'last' => date('d/m/Y G:i',strtotime($friend->last_access)),
			"chats" => $chats
		];

		// get images for the web
		$images = [];
		if($request->environment == "web") {
			foreach ($chats as $chat) {
				$images[] = $chat->picture;
			}
		}

		// send information to the view
		$response = new Response();
		$response->setResponseSubject("Charla con @$friendUsername");
		$response->createFromTemplate("chats.tpl", $content, $images);
		return $response;
	}

	/**
	 *
	 *@author ricardo
	 *@param Request
	 *@return Response
	 */
	 public function _borrar(Request $request){
		 $to_email=$this->utils->getEmailFromUsername($request->query);
		 $social = new Social();
		 $social->chatOcult($request->email,$to_email);
		 $request->query=null;
		 return $this->_main($request);
	 }

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 */
	public function _post(Request $request, $friendUsername = false, $friendId = false, $note = false)
	{
		$response = new Response();

		// load params if not passed
		if(empty($friendUsername) || empty($friendId) || empty($note))
		{
			// get the friend username
			$argument = explode(" ", $request->query);
			$friendUsername = str_replace("@", "", $argument[0]);

			// get the friend email
			$friendId = $this->utils->getEmailFromUsername($friendUsername);
			if(empty($friendId)) return $response->createFromText("El nombre de usuario @$friendUsername no parece existir. Verifica que sea correcto e intenta nuevamente.", "ERROR", "Wrong username");

			// get the text for the note
			unset($argument[0]);
			$note = implode(" ", $argument);
			if(empty($note)) return $response->createFromText("No has pasado un texto, no podemos enviar una nota en blanco. El asunto debe ser: NOTA @username TEXTO A ENVIAR", "ERROR", "No text to save");
		}

		$blocks=$this->isBlocked($request->email,$friendId);
		if ($blocks->blocked>0 || $blocks->blockedByMe>0) {
			$response->subject="Lo sentimos";
			$response->createFromText("Lo sentimos, usted no puede escribirle a @$friendUsername ya que ha sido bloqueado por esa persona, o usted lo ha bloqueado");
			return $response;
		}

		// store the note in the database
		$note = Connection::escape($note);
		$note = substr($note, 0, 499);
		Connection::query("INSERT INTO _note (from_user, to_user, `text`) VALUES ($request->userId,$friendId,'$note')");

		// send notification for the app
		$yourUsername = $this->utils->getUsernameFromEmail($request->email);
		$this->utils->addNotification($friendId, "chat", "@$yourUsername le ha enviado una nota", "CHAT @$yourUsername");

		// send push notification for users of Piropazo
		$pushNotification = new PushNotification();
		$appid = $pushNotification->getAppId($friendId, "piropazo");
		if($appid) {
			$personFrom = $this->utils->getPerson($request->userId);
			$personTo = $this->utils->getPerson($friendId);
			$pushNotification->piropazoChatPush($appid, $personFrom, $personTo, $note);
			return $response;
		}

		// send web notification for users of Pizarra
		$appid = $pushNotification->getAppId($friendId, "pizarra");
		if($appid) {
			$pushNotification->pizarraChatReceived($appid, $yourUsername, $note);
			return $response;
		}
		$friend=$this->utils->getPerson($friendId);
		// create the response
		$social = new Social();
		$notes = $social->chatConversation($request->userId, $friendId);

		$content = [
			"friendUsername" => $friendUsername,
			"online" => $friend->online,
			'last' => date('d/m/Y G:i',strtotime($friend->last_access)),
			"chats" => $notes
		];

		$response->setResponseSubject("Charla con @$friendUsername");
		$response->createFromTemplate("chats.tpl", $content);
		return $response;
	}

	/**
	 * Return the count of all unread notes. Useful for the API
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 */
	public function _unread(Request $request)
	{
		// get count of unread notes
		$connection = new Connection();
		$notes = $connection->query("
			SELECT B.username, MAX(send_date) as sent, COUNT(B.username) as counter
			FROM _note A LEFT JOIN person B
			ON A.from_user = B.email
			WHERE to_user = '{$request->email}'
			AND NOT EXISTS (SELECT id FROM relations WHERE user1 = '{$request->email}' AND user2 = A.from_user AND type = 'blocked' AND confirmed = 1)
			AND read_date IS NULL
			GROUP BY B.username
			ORDER BY sent DESC;");

		// get the total counter
		$total = 0;
		$social = new Social();
		foreach($notes as $k => $note) {
			$total += $note->counter;
			$notes[$k]->profile = $this->utils->getPerson($this->utils->getEmailFromUsername($note->username));
			$notes[$k]->last_note = $social->chatConversation($request->email, $notes[$k]->profile->email, 1);
		}

		// respond back to the API
		$response = new Response();
		$jsonResponse = ["code" => "ok", "total" => $total, "items" => $notes];
		return $response->createFromJSON(json_encode($jsonResponse));
	}

	/**
	 * Sub-service ONLINE
	 *
	 * @param \Request $request
	 * @return \Response
	 */
	public function _online(Request $request)
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
			$response = new Response();
			$response->setResponseSubject("No hay usuarios conectados");
			$response->createFromText("No hay nadie conectado en este momento. Por favor vuelva a intentar mas tarde.");
			return $response;
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
		$response = new Response();
		$response->setResponseSubject("Usuarios conectados");
		$response->createFromTemplate("online.tpl", ['users' => $online], $images);
		return $response;
	}

	/**
	 * Get if the user is blocked or has been blocked by
	 * @author ricardo@apretaste.com
	 * @param String $user1
	 * @param String $user2
	 * @return Object
	 */
	private function isBlocked(String $user1, String $user2){
		$res=new stdClass();
		$res->blocked = false;
		$res->blockedByMe = false;

		$r = Connection::query("SELECT *
		FROM ((SELECT COUNT(user1) AS blockedByMe FROM relations
				WHERE user1 = '$user1' AND user2 = '$user2'
				AND `type` = 'blocked' AND confirmed=1) AS A,
				(SELECT COUNT(user1) AS blocked FROM relations
				WHERE user1 = '$user2' AND user2 = '$user1'
				AND `type` = 'blocked' AND confirmed=1) AS B)");

		$res->blocked=($r[0]->blocked>0)?true:false;
		$res->blockedByMe=($r[0]->blockedByMe>0)?true:false;

		return $res;
	}
}
