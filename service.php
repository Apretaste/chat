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

			// decide where to say escribir o responder
			$btnCaption = ($friendUsername == $notes[0]->username) ? "Escribir" : "Responder";

			// prepare the datails for the view
			$responseContent = array(
				"friendUsername" => $friendUsername,
				"btnCaption" => $btnCaption,
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
		// POST A NOTE WHEN SUBJECT=NOTA MY NOTE HERE
		//

		// store note in database
		$connection->deepQuery("INSERT INTO _note (from_user, to_user, `text`) VALUES ('{$request->email}','$friendEmail','$note');");

		// get the conversation between you and your friend
		$notes = $this->getConversation($request->email, $friendEmail);

		// create a notification for you and your friend
		$yourUsername = $this->utils->getUsernameFromEmail($request->email);
		$this->utils->addNotification($request->email, "nota", "Enviamos su nota a @$friendUsername", "NOTA @$friendUsername");
		$this->utils->addNotification($friendEmail, "nota", "@$yourUsername le ha enviado una nota", "NOTA @$yourUsername");

		// prepare the datails for the view
		$btnCaption = ($friendUsername == $notes[0]->username) ? "Responder" : "Escribir";
		$responseContent = array(
			"friendUsername" => $friendUsername,
			"btnCaption" => $btnCaption,
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

		// get the friend email
		$argument = explode(" ", $request->query);
		$friendUsername = str_replace("@", "", $argument[0]);
		$find = $connection->deepQuery("SELECT email FROM person WHERE username = '$friendUsername';");
		if (empty($find)) return $response->createFromJSON('{"code":"ERROR", "message":"Wrong username"}');
		$friendEmail = $find[0]->email;

		// get the date/time passed as mysql notation
		$filterDateTime = isset($argument[1]) ? date("Y-m-d H:i:s", strtotime($argument[1])) : false;

		// get the array of notes
		$notes = $this->getConversation($request->email, $friendEmail, $filterDateTime, 100);
		return $response->createFromJSON(json_encode($notes));
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

		// store the note in database
		$connection->deepQuery("INSERT INTO _note (from_user, to_user, `text`) VALUES ('{$request->email}','$friendEmail','$note');");

		// create a notification for you and your friend
		$yourUsername = $this->utils->getUsernameFromEmail($request->email);
		$this->utils->addNotification($request->email, "nota", "Enviamos su nota a @$friendUsername", "NOTA @$friendUsername");
		$this->utils->addNotification($friendEmail, "nota", "@$yourUsername le ha enviado una nota", "NOTA @$yourUsername");

		// return response
		return $response->createFromJSON('{"code":"OK"}');
	}


	/**
	 * Return a list of notes between $email1 & $email2
	 *
	 * @author salvipascual
	 * @param String $email1
	 * @param String $email2
	 * @param String $date, on the form YYYY-MM-DD
	 * @param string $limit, integer number of max rows
	 * @return array
	 */
	private function getConversation($email1, $email2, $date=false, $limit=20)
	{
		// get a default date a year ago
		if(empty($date)) $date = date('Y-m-d', strtotime('-1 year'));

		// retrieve conversation between users
		$connection = new Connection();
		return $connection->deepQuery("
			SELECT * FROM (
				SELECT B.username, A.text, A.send_date as sent
				FROM _note A LEFT JOIN person B
				ON A.to_user = B.email
				WHERE from_user = '$email1' AND to_user = '$email2'
				AND A.send_date > '$date'
				UNION
				SELECT B.username, A.text, A.send_date as sent
				FROM _note A LEFT JOIN person B
				ON A.to_user = B.email
				WHERE from_user = '$email2' AND to_user = '$email1'
				AND A.send_date > '$date') C
			ORDER BY sent DESC
			LIMIT $limit");
	}
}
