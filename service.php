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
			$social = new Social();
			$notes = $social->chatsOpen($request->email);

			// show home page if no notes found
			if(empty($notes)) {
				$response = new Response();
				$response->setResponseSubject("Lista de chats abiertos");
				$response->createFromTemplate("home.tpl", []);
				return $response;
			}

			// send data to the view
			$response = new Response();
			$response->setResponseSubject("Lista de chats abiertos");
			$response->createFromTemplate("open.tpl", ["notes" => $notes]);
			return $response;
		}

		// get the username of the note
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$friendEmail = $this->utils->getEmailFromUsername($friendUsername);

		// check if the username is valid
		if(empty($friendEmail)) {
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
			return $this->_get($request, $friendEmail);
		}

		// get text of the the note to post
		unset($argument[0]);
		$note = implode(" ", $argument);

		//
		// POST A NOTE WHEN SUBJECT=NOTA @username MY NOTE HERE
		//
		return $this->_post($request, $friendUsername, $friendEmail, $note);
	}

	/**
	 * Get latest chats after certain NOTE_ID
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 */
	public function _get(Request $request, $friendEmail=false)
	{
		// get the username and ID of the query
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$lastID = isset($argument[1]) ? $argument[1] : 0;

		// get the friend email if not passed
		if(empty($friendEmail)) {
			$friendEmail = $this->utils->getEmailFromUsername($friendUsername);
			if( ! $friendEmail) {
				$response = new Response();
				return $response->createFromJSON('{"code":"ERROR", "message":"Wrong username"}');
			}
		}

		// get the array of notes
		$social = new Social();
		$notes = $social->chatConversation($request->email, $friendEmail, $lastID);

		// get the new last ID and remove ID for each note
		$newLastID = 0;
		$chats = [];
		foreach($notes as $nota) {
			if($nota->id > $newLastID) $newLastID = $nota->id; // for the piropazo app
			$chat = new stdClass();
			$chat->username = $nota->username;
			$chat->gender = $nota->gender;
			$chat->picture = $nota->picture_public;
			$chat->text = $nota->text;
			$chat->sent = $nota->sent;
			$chat->read = $nota->read;
			$chats[] = $chat;
		}

		// prepare the details for the view
		$content = [
			"code" => "ok",
			"last_id" => $newLastID,
			"friendUsername" => $friendUsername,
			"chats" => $chats
		];

		// send information to the view
		$response = new Response();
		$response->setResponseSubject("Charla con @$friendUsername");
		$response->createFromTemplate("chats.tpl", $content);
		return $response;
	}

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 */
	public function _post(Request $request, $friendUsername = false, $friendEmail = false, $note = false)
	{
		$response = new Response();

		// load params if not passed
		if(empty($friendUsername) || empty($friendEmail) || empty($note))
		{
			// get the friend username
			$argument = explode(" ", $request->query);
			$friendUsername = str_replace("@", "", $argument[0]);

			// get the friend email
			$friendEmail = $this->utils->getEmailFromUsername($friendUsername);
			if(empty($friendEmail)) return $response->createFromText("El nombre de usuario @$friendUsername no parece existir. Verifica que sea correcto e intenta nuevamente.", "ERROR", "Wrong username");

			// get the text for the note
			unset($argument[0]);
			$note = implode(" ", $argument);
			if(empty($note)) return $response->createFromText("No has pasado un texto, no podemos enviar una nota en blanco. El asunto debe ser: NOTA @username TEXTO A ENVIAR", "ERROR", "No text to save");
		}

		// store the note in the database
		$connection = new Connection();
		$note = $connection->escape($note);
		$note = substr($note, 0, 499);
		$connection->query("INSERT INTO _note (from_user, to_user, `text`) VALUES ('{$request->email}','$friendEmail','$note')");

		// send notification for the app
		$yourUsername = $this->utils->getUsernameFromEmail($request->email);
		$this->utils->addNotification($friendEmail, "chat", "@$yourUsername le ha enviado una nota", "CHAT @$yourUsername");

		// send push notification for users of Piropazo
		$pushNotification = new PushNotification();
		$appid = $pushNotification->getAppId($friendEmail, "piropazo");
		if($appid) {
			$personFrom = $this->utils->getPerson($request->email);
			$personTo = $this->utils->getPerson($friendEmail);
			$pushNotification->piropazoChatPush($appid, $personFrom, $personTo, $note);
			return $response;
		}

		// send web notification for users of Pizarra
		$appid = $pushNotification->getAppId($friendEmail, "pizarra");
		if($appid) {
			$pushNotification->pizarraChatReceived($appid, $yourUsername, $note);
			return $response;
		}

		// create the response
		$social = new Social();
		$notes = $social->chatConversation($request->email, $friendEmail);
		$response->setResponseSubject("Nueva nota de @$yourUsername");
		$response->createFromTemplate("chats.tpl", ["friendUsername" => $friendUsername, "chats" => $notes]);
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

		// send info to the view
		$response = new Response();
		$response->setResponseSubject("Usuarios conectados");
		$response->createFromTemplate("online.tpl", ['users' => $online]);
		return $response;
	}
}
