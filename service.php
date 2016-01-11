<?php

class Nota extends Service {

	/**
	 * Function executed when the service is called
	 *
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request) {

		$argument = trim($request->query);

		$parts = explode(' ', $argument);

		$un = false;
		$nota = false;

		if (isset($parts[0]))
			$un = $parts[0];

		if (isset($parts[1])) {
			$nota = trim(substr($argument, strlen($un)));
			if ($nota == '')
				$nota = false;
		}

		$db = new Connection();

		$friend = false;
		$person = $this->utils->getPerson($request->email);

		if ($un !== false && !empty($un)) {
			$find = $db->deepQuery("SELECT email FROM person WHERE username = '$un';");

			if (!isset($find[0])) {
				$response = new Response();
				$response->setResponseSubject("No se pudo enviar la nota pues el usuario $un no existe");
				$response->createFromText("El usuario <b>$un</b> no existe en Apretaste. Para enviar la nota escriba en el asunto la palabra NOTA seguida del nombre de usuario de su amigo y luego el mensaje a enviar. Por ejemplo: <b>NOTA amigo1 Hola amigo</b>.");
				return $response;
			}

			$friend = $this->utils->getPerson($find[0]->email);
			$sqlnotes = "SELECT *, (SELECT username FROM person WHERE person.email = _note.from_user) as from_username FROM _note WHERE (from_user = '{$person->email}' AND to_user = '{$friend->email}') OR (to_user = '{$person->email}' AND from_user = '{$friend->email}') ORDER BY send_date DESC;";
		}


		if (empty($un) || $un === false) {
			$contacts = $db->deepQuery("SELECT (select username FROM person WHERE person.email = subq.username) as username,  
                                    subq.username as email FROM (SELECT from_user as username FROM _note WHERE to_user = '{$person->email}'
                                  UNION SELECT to_user as username FROM _note WHERE from_user = '{$person->email}') as subq WHERE username <> '' AND username IS NOT NULL GROUP BY username");

			if (isset($contacts[0])) {
				$response = new Response();
				$response->setResponseSubject("Deseas enviar una nota?");

				foreach ($contacts as $k => $contact) {
					$sqlnotes = "SELECT *, (SELECT username FROM person WHERE person.email = _note.from_user) as from_username FROM _note WHERE (from_user = '{$person->email}' AND to_user = '{$contact->email}') OR (to_user = '{$person->email}' AND from_user = '{$contact->email}') ORDER BY send_date DESC";
					$last_note = $db->deepQuery("SELECT from_username, text FROM ($sqlnotes) as subq2 LIMIT 1 OFFSET 0;");
					$contacts[$k]->last_note = array(
                        'from' => $last_note[0]->from_username,
                        'note' => $last_note[0]->text
                    );
				}

				$response->createFromTemplate("nouser.tpl", array("contacts" => $contacts));
				return $response;
			} else {
				$response = new Response();
				$response->setResponseSubject("Deseas enviar una nota?");
				$response->createFromTemplate("nouser.tpl", array("contacts" => false));
				return $response;
			}
		}


		if ($nota !== false) {
			$db->deepQuery("INSERT INTO _note (from_user, to_user, text) VALUES ('{$request->email}','{$friend->email}','$nota');");

			$notes = $db->deepQuery($sqlnotes);

			$response = new Response();
			$response->setResponseEmail($friend->email);
			$response->setResponseSubject("Nota de {$person->username}");
			$response->createFromTemplate("basic.tpl", array('username' => $person->username, 'notes' => $notes));

			$response2 = new Response();
			$response2->setResponseSubject("Le enviamos la nota a tu amigo");
			$response2->createFromTemplate("basic.tpl", array('username' => $un, 'notes' => $notes));

			return array($response, $response2);
		}

		$notes = $db->deepQuery($sqlnotes);

		$response = new Response();
		$response->setResponseSubject("Notas entre {$friend->username} y tu");
		$response->createFromTemplate("basic.tpl", array('username' => $friend->username, 'notes' => $notes));

		return $response;

	}

}
