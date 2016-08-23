<?php

class Nota extends Service
{
	/**
	 * Get the list of conversations
	 *
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request)
	{
		$argument = trim($request->query);
		$person = $this->utils->getPerson($request->email);

		// Extracting username and text
		$parts = explode(' ', $argument);

		$un = false;
		$nt = false;

		if (isset($parts[0]) && !empty($parts[0]))
		{
			$un = $parts[0];
		}

		if (isset($parts[1]))
		{
			$nt = trim(substr($argument, strlen($un)));
			if ($nt == '')
				$nt = false;
		}

		if ($un !== false)
			if ($un[0] == '@')
				$un = substr($un, 1);

		// Connecting to database
		$db = new Connection();

		// If subject's query is empty ...
		if ($un === false)
		{
			// Searching contacts of the current user
			$contacts = $db->deepQuery("SELECT (select username FROM person WHERE person.email = subq.username) as username,  
										subq.username as email 
										FROM (SELECT from_user as username FROM _note WHERE to_user = '{$person->email}'
										UNION SELECT to_user as username FROM _note WHERE from_user = '{$person->email}') as subq 
										WHERE username <> '' AND username IS NOT NULL GROUP BY username");

			// Preparing contacts list
			if (is_array($contacts))
				foreach ($contacts as $k => $contact)
				{
					$last_note = $this->getConversation($person->email, $contact->email, 1);
					$contacts[$k]->last_note = array(
						'from' => $last_note[0]->from_username,
						'note' => $last_note[0]->text,
						'date' => $last_note[0]->date);
				}

			// Return the response
			$response = new Response();
			$response->setResponseSubject("Deseas enviar una nota?");
			$response->createFromTemplate("nouser.tpl", array("contacts" => $contacts));
			return $response;
		}

		// Searching the user $un in the database
		$friend = false;
		$find = $db->deepQuery("SELECT email FROM person WHERE username = '$un';");

		// The user $un not exists
		if (!isset($find[0]))
		{
			$response = new Response();
			$response->setResponseSubject("El usuario @$un no existe");
			$response->createFromTemplate("user_not_exists.tpl", array("username" => $un));
			return $response;
		}

		$friend = $this->utils->getPerson($find[0]->email);

		// Sending the note
		if ($nt !== false)
		{
			if ($nt == 'Reemplace este texto por su nota') {
                $response = new Response();
                $response->setResponseSubject(
                        "No reemplazaste el texto por tu nota");
                $response->createFromTemplate("howto.tpl", array());
                return $response;
            }
			
			// Store note in database
			$db->deepQuery("INSERT INTO _note (from_user, to_user, text) VALUES ('{$request->email}','{$friend->email}','$nt');");

			// Retrieve notes between users
			$notes = $this->getConversation($person->email, $friend->email);

			// Response for friend
			$response = new Response();
			$response->setResponseEmail($friend->email);
			$response->setResponseSubject("Nueva nota de @{$person->username}");
			$response->createFromTemplate("basic.tpl", array('username' => $person->username, 'notes' => $notes));

			// Generate a notification
			$this->utils->addNotification($request->email, 'nota', "Enviamos su nota a @$un", 'NOTA');
			
			return $response;
		}

		// Empty note, sending conversation...
		$notes = $this->getConversation($person->email, $friend->email);
		$response = new Response();
		$response->setResponseSubject("Su charla con @{$friend->username}");
		$response->createFromTemplate("basic.tpl", array('username' => $friend->username, 'notes' => $notes));
		return $response;
	}

	/**
	 * Return a list of notes between $email1 & $email2 
	 * 
	 * @param string $email1
	 * @param string $email2
	 * @return array
	 */
	private function getConversation($email1, $email2, $limit = 20)
	{
		// SQL for retrieve conversation between users
		$sql = "SELECT *, 
				date_format(send_date,'%d/%m/%y %h:%i%p') as date, 
				(SELECT username FROM person WHERE person.email = _note.from_user) as from_username 
				FROM _note 
				WHERE (from_user = '{$email1}' AND to_user = '{$email2}') 
				OR (to_user = '{$email1}' AND from_user = '{$email2}') 
				ORDER BY send_date DESC
				LIMIT $limit;";

		$db = new Connection();
		$find = $db->deepQuery($sql);

		return $find;
	} 
}
