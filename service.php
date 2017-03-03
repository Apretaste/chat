<?php

class Nota extends Service
{
	/**
	 * Get the list of conversations, or post a note
	 *
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request)
	{
		// Connecting to database
		$connection = new Connection();

		//
		// SHOW THE LIST OF OPEN CHATS WHEN SUBJECT=NOTA
		//
		if ( ! $request->query)
		{
			// Searching contacts of the current user
			$notes = $connection->deepQuery("
				SELECT B.username, MAX(send_date) as sent
				FROM _note A RIGHT JOIN person B
				ON A.to_user = B.email
				WHERE from_user = '{$request->email}'
				GROUP BY to_user
				ORDER BY send_date DESC");

			// Return the response
			$response = new Response();
			$response->setResponseSubject("Lista de chats abiertos");
			$response->createFromTemplate("open.tpl", array("notes" => $notes));
			return $response;
		}

		// check that the username of the note is valid
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$find = $connection->deepQuery("SELECT email FROM person WHERE username = '$friendUsername';");
		if (empty($find))
		{
			$response = new Response();
			$response->setResponseSubject("El usuario @$username no existe");
			$response->createFromTemplate("user_not_exists.tpl", array("username"=>$friendUsername));
			return $response;
		}
		$friendEmail = $find[0]->email;

		//
		// GET A LIST OF THE CHATS WHEN SUBJECT=NOTA @USERNAME
		//
		if(count($argument) == 1)
		{
			// get the conversation between you and your friend
			$notes = $this->getConversation($request->email, $friendEmail);

			// prepare the datails for the view
			$responseContent = array(
				"friendUsername" => $friendUsername,
				"notes" => $notes
			);

			// sending conversation to the view
			$response = new Response();
			$response->setResponseSubject("Su charla con @$friendUsername");
			$response->createFromTemplate("chats.tpl", $responseContent);
			return $response;
		}

		// get text of the the note to post
		unset($argument[0]);
		$note = implode(" ", $argument);

		// if you are trying to post using the example text, send the help document
		if ($note == 'Reemplace este texto por su nota')
		{
			$response = new Response();
			$response->setResponseSubject("No reemplazaste el texto por tu nota");
			$response->createFromTemplate("howto.tpl", array());
			return $response;
		}

		//
		// POST A NOTE WHEN SUBJECT=NOTA @username MY NOTE HERE
		//

		// store note in the database
		$connection->deepQuery("INSERT INTO _note (from_user, to_user, `text`) VALUES ('{$request->email}','$friendEmail','$note');");

		// prepare notification
		$pushNotification = new PushNotification();
		$appid = $pushNotification->getAppId($friendEmail, "piropazo");
		$yourUsername = $this->utils->getUsernameFromEmail($request->email);

		// send push notification for users with the App
		if($appid)
		{
			$personFrom = $this->utils->getPerson($request->email);
			$personTo = $this->utils->getPerson($friendEmail);
			$pushNotification->piropazoChatPush($appid, $personFrom, $personTo, $note);
		}
		// post an internal notification for the user
		else
		{
			$this->utils->addNotification($request->email, "nota", "Enviamos su nota a @$yourUsername", "NOTA @$friendUsername");
			$this->utils->addNotification($friendEmail, "nota", "@$yourUsername le ha enviado una nota", "NOTA @$yourUsername");
		}

		// get the conversation between you and your friend
		$notes = $this->getConversation($request->email, $friendEmail);

		// prepare the datails for the view
		$responseContent = array(
			"friendUsername" => $yourUsername,
			"notes" => $notes
		);

		// Send the response email to your friend
		$response = new Response();
		$response->setResponseEmail($friendEmail);
		$response->setResponseSubject("Nueva nota de @$yourUsername");
		$response->createFromTemplate("chats.tpl", $responseContent);
		return $response;
	}

	/**
	 * Get latest chats after certain date/time
	 * Pass the time as YYYY-MM-DDTHH:MM:SS
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 * */
	public function _get(Request $request)
	{
		$connection = new Connection();
		$response = new Response();

		// get the username and ID of the query
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$lastID = isset($argument[1]) ? $argument[1] : 0;

		// get the friend email
		$friendEmail = $this->utils->getEmailFromUsername($friendUsername);
		if ( ! $friendEmail) return $response->createFromJSON('{"code":"ERROR", "message":"Wrong username"}');

		// get the array of notes
		$notes = $this->getConversation($request->email, $friendEmail, $lastID);

		// get the new last ID and remove ID for each note
		$newLastID = 0;
		foreach ($notes as $nota)
		{
			if($nota->id > $newLastID) $newLastID = $nota->id;
			unset($nota->id);
		}

		// return json
		$json = '{"code":"ok","last_id":"'.$newLastID.'","chats":'.json_encode($notes).'}';
		return $response->createFromJSON($json);
	}

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @api
	 * @author salvipascual
	 * @param Request
	 * @return Response
	 * */
	public function _post(Request $request)
	{
		$connection = new Connection();
		$response = new Response();

		// get the friend username and email
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$find = $connection->deepQuery("SELECT email FROM person WHERE username = '$friendUsername';");
		if (empty($find)) return $response->createFromJSON('{"code":"ERROR", "message":"Wrong username"}');
		$friendEmail = $find[0]->email;

		// get the text for the note
		unset($argument[0]);
		$note = implode(" ", $argument);
		if(empty($note)) return $response->createFromJSON('{"code":"ERROR", "message":"No text to save"}');

		// store the note in the database
		$connection->deepQuery("INSERT INTO _note (from_user, to_user, `text`) VALUES ('{$request->email}','$friendEmail','$note');");

		// prepare notification
		$pushNotification = new PushNotification();
		$appid = $pushNotification->getAppId($friendEmail, "piropazo");

		// send push notification for users with the App
		if($appid)
		{
			$personFrom = $this->utils->getPerson($request->email);
			$personTo = $this->utils->getPerson($friendEmail);
			$pushNotification->piropazoChatPush($appid, $personFrom, $personTo, $note);
		}
		// post an internal notification for the user
		else
		{
			$yourUsername = $this->utils->getUsernameFromEmail($request->email);
			$this->utils->addNotification($friendEmail, "nota", "@$yourUsername le ha enviado una nota", "NOTA @$yourUsername");
			$this->utils->addNotification($request->email, "nota", "Enviamos su nota a @$friendUsername", "NOTA @$friendUsername");
		}

		// return response
		return $response->createFromJSON('{"code":"OK"}');
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
		$notes = $connection->deepQuery("
			SELECT B.username, MAX(send_date) as sent, COUNT(B.username) as counter
			FROM _note A LEFT JOIN person B
			ON A.from_user = B.email
			WHERE to_user = '{$request->email}'
			AND read_date IS NULL
			GROUP BY B.username");

		// get the total counter
		$total = 0;
		foreach ($notes as $note) $total += $note->counter;

		// respond back to the API
		$response = new Response();
		$jsonResponse = array("code" => "ok", "total"=>$total, "items" => $notes);
		return $response->createFromJSON(json_encode($jsonResponse));
	}

	/**
	 * Return a list of notes between $email1 & $email2
	 *
	 * @author salvipascual
	 * @param String $email1
	 * @param String $email2
	 * @param String $lastID, get all from this ID
	 * @param string $limit, integer number of max rows
	 * @return array
	 */
	private function getConversation($yourEmail, $friendEmail, $lastID=0, $limit=20)
	{
		// if a last ID is passed, do not cut the result based on the limit
		$setLimit = ($lastID > 0) ? "" : "LIMIT $limit";

		// retrieve conversation between users
		$connection = new Connection();
		$notes = $connection->deepQuery("
			SELECT * FROM (
				SELECT A.id, B.username, A.text, A.send_date as sent, A.read_date as `read`
				FROM _note A LEFT JOIN person B
				ON A.from_user = B.email
				WHERE from_user = '$yourEmail' AND to_user = '$friendEmail'
				AND A.id > '$lastID'
				UNION
				SELECT A.id, B.username, A.text, A.send_date as sent, CURRENT_TIMESTAMP as `read`
				FROM _note A LEFT JOIN person B
				ON A.from_user = B.email
				WHERE from_user = '$friendEmail' AND to_user = '$yourEmail'
				AND A.id > '$lastID') C
			ORDER BY sent DESC $setLimit");

		// mark the other person notes as unread
		if($notes)
		{
			$lastNoteID = end($notes)->id;
			$connection->deepQuery("
				UPDATE _note
				SET read_date = CURRENT_TIMESTAMP
				WHERE read_date is NULL
				AND from_user = '$friendEmail'
				AND id >= $lastNoteID");
		}

		return $notes;
	}
}
