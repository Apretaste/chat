<?php

class Nota extends Service {

    static $results = array();

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

        if ($un === false || $nota === false) {
            $response = new Response();
            $response->setResponseSubject("Faltan datos para poder enviar la nota");
            $response->createFromText("Para enviar la nota escriba en el asunto la palabra NOTA seguida del nombre de usuario de su amigo y luego el mensaje a enviar. Por ejemplo: NOTA amigo1 Hola amigo");
            return $response;
        }

        $db = new Connection();
        $find = $db->deepQuery("SELECT email FROM person WHERE username = '$un';");

        if (!isset($find[0])) {
            $response = new Response();
            $response->setResponseSubject("No se pudo enviar la nota pues el usuario $un no existe");
            $response->createFromText("El usuario $un no existe en Apretaste. Para enviar la nota escriba en el asunto la palabra NOTA seguida del nombre de usuario de su amigo y luego el mensaje a enviar. Por ejemplo: NOTA amigo1 Hola amigo");
            return $response;
        }

        $friend = $this->utils->getPerson($find[0]->email);
        $person = $this->utils->getPerson($request->email);

        $db->deepQuery("INSERT INTO _note (from_user, to_user, text) VALUES ('{$request->email}','{$friend->email}','$nota');");
        
        $notes = $db->deepQuery("SELECT *, (SELECT username FROM person WHERE person.email = _note.from_user) as from_username FROM _note WHERE (from_user = '{$person->email}' AND to_user = '{$friend->email}') OR (to_user = '{$person->email}' AND from_user = '{$friend->email}') ORDER BY send_date DESC;");

        $response = new Response();
        $response->setResponseEmail($friend->email);
        $response->setResponseSubject("Nota de {$person->username}");
        $response->createFromTemplate("basic.tpl", array('username' => $person->username, 'notes' => $notes));

        $response2 = new Response();
        $response2->setResponseSubject("Le enviamos la nota a tu amigo");
        $response2->createFromTemplate("basic.tpl", array('username' => $un, 'notes' => $notes));

        return array($response, $response2);
    }

}
