<?php

use Apretaste\Core;
use Apretaste\Alert;
use Apretaste\Utils;
use Apretaste\Chats;
use Apretaste\Bucket;
use Apretaste\Images;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Database;
use Apretaste\Challenges;
use Apretaste\Notifications;

class Service
{
	/**
	 * Get the list of open conversations
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert|\Apretaste\Alert
	 * @throws Exception
	 * @author salvipascual
	 */
	public function _main(Request $request, Response $response)
	{
		$data = $request->input->data;
		$needle = $data->username ?? $data->id ?? $data->userId ?? false;

		// chat with a user
		if ($needle) {
			$this->_chat($request, $response);
			return;
		}
		// get the list of people chatting with you
		$chats = Chats::open($request->person->id);

		// get content for the view
		$content = [
			'chats' => $chats,
			'myUser' => $request->person->id
		];

		// send data to the view
		// $response->setCache('hour');
		$response->setTemplate('main.ejs', $content);
	}

	/**
	 * Get the list of conversations
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @throws Exception
	 * @author salvipascual
	 */
	public function _chat(Request $request, Response $response)
	{
		$data = $request->input->data;
		$needle = $data->username ?? $data->id ?? $data->userId ?? false;

		$user = $needle ? Person::find($needle) : false;
		// ensure a person Id is passed
		if (!$user) {
			$response->setCache();
			$response->setTemplate('message.ejs', [
				'header' => 'Usuario inexistente',
				'icon' => 'sentiment_neutral',
				'text' => 'Lo sentimos, el usuario que usted busca no existe. Puede que halla dejado de usar la app. Busque otro usuario y comience a chatear.',
				'button' => ['href' => 'CHAT', 'caption' => 'Atras']
			]);
			return;
		}

		if (!$request->person->isFriendOf($user->id)) {
			$response->setTemplate('message.ejs', [
				'header' => 'Oops!',
				'icon' => 'sentiment_neutral',
				'text' => "Parece que tú y @{$user->username} aún no son amígos, envíale una solicitud desde su perfíl para poder chatear.",
				'button' => ['href' => 'PERFIL', 'caption' => 'Ir al perfíl', 'data' => "username: {$user->id}"]
			]);
			return;
		}

		// get and display messages
		$chats = Chats::conversation($request->person->id, $user->id);

		$images = [];
		$files = [];
		foreach ($chats as $chat) {
			if ($chat->image) {
				$images[] = Bucket::getPathByEnvironment('chat', $chat->image);
			}

			if ($chat->voice) {
				$files[] = Bucket::getPathByEnvironment('voices', $chat->voice);
			}
		}

		// get content for the view
		$content = [
			'messages' => $chats,
			'id' => $user->id,
			'online' => $user->isOnline,
			'gender' => $user->gender,
			'username' => $user->username,
			'avatar' => $user->avatar,
			'isInfluencer' => $user->isInfluencer,
			'influencerData' => $user->getInfluencerData(),
			'avatarColor' => $user->avatarColor,
			'experience' => $user->experience,
			'myAvatar' => $request->person->avatar,
			'myColor' => $request->person->avatarColor,
			'myGender' => $request->person->gender,
			'myUsername' => $request->person->username,
			'iAmInfluencer' => $request->person->isInfluencer,
			'last' => date('d/m/Y h:i a', strtotime($user->lastAccess))
		];

		// send data to the view
		$response->setTemplate('chat.ejs', $content, $images, $files);
	}

	/**
	 * Borrar un chat del usuario
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _borrar(Request $request, Response $response)
	{
		$deleteType = $request->input->data->type ?? 'chat';
		$idToHide = $request->input->data->id;

		if ($deleteType === 'chat') {
			Chats::hide($request->person->id, $idToHide);

			// hide for both
			Chats::hide($idToHide, $request->person->id);
		}

		if ($deleteType === 'message') {
			$userToId = $request->input->data->userToId;

			if ($idToHide == 'last') {
				$idToHide = Database::queryFirst("SELECT MAX(id) AS id FROM _note WHERE from_user={$request->person->id}")->id;
			}

			$result = Database::queryFirst("SELECT id FROM _note WHERE from_user={$request->person->id} AND id=$idToHide");

			if ($result != null) {
				Chats::hideMessage($request->person->id, $idToHide);

				// hide for both
				Chats::hideMessage($userToId, $idToHide);
			}
		}
	}

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author salvipascual
	 */
	public function _escribir(Request $request, Response $response)
	{
		if (!isset($request->input->data->id)) {
			$response->setContent([
				'error' => true,
				'message' => 'Mensaje sin destinatario'
			]);
			return;
		}

		$userTo = Person::find($request->input->data->id);
		if (!$userTo) {
			$response->setContent([
				'error' => true,
				'message' => 'Usuario no encontrado'
			]);
			return;
		}

		$image = $request->input->data->image ?? false;
		$imageName = $request->input->data->imageName ?? false;
		$voiceName = $response->input->data->voiceName ?? false;
		$message = $request->input->data->message ?? '';

		if (!$image && !$imageName && !$voiceName && empty($message)) {
			$response->setContent([
				'error' => true,
				'message' => 'Mensaje sin contenido'
			]);
			return;
		}

		$fileName = '';
		$voiceFileName = '';

		// get the image name and path
		if ($image || $imageName) {
			$fileName = Utils::randomHash();

			if (!$image) {
				$image = base64_encode(file_get_contents($request->input->files[$imageName]));
			}

			$filePath = Images::saveBase64Image($image, TEMP_PATH . $fileName);
			$fileName = basename($filePath);
			if (stripos($fileName, '.') === false) $fileName .= '.jpg';
			Bucket::save("chat", $filePath, $fileName);
		}

		if ($voiceName) {
			$filePath = $request->input->files[$voiceName];
			$voiceFileName = Utils::randomHash() . '.' . explode('.', $voiceName)[1];

			Bucket::save("voices", $filePath, "$voiceFileName");
		}

		if ($request->person->isBlocked($userTo->id)) {
			$text = "Su mensaje para @{$userTo->username} no pudo ser entregado, es posible que usted haya sido bloqueado por esa persona.";
			Notifications::alert($request->person->id, $text, 'error', "{'command':'PERFIL', 'data':{'id':'{$userTo->id}'}");

			$response->setContent([
				'error' => true,
				'message' => 'No puedes escribirle a este usuario'
			]);
			return;
		}

		// store the note in the database
		$message = Database::escape($request->input->data->message ?? '', 499);
		$newMessageId = Database::query("INSERT INTO _note (from_user, to_user, `text`, image, voice) VALUES ({$request->person->id},{$userTo->id},'$message', '$fileName', '$voiceFileName')");

		// send notification for the app
		$text = "@{$request->person->username} te ha enviado una nota";
		Notifications::alert(
			$userTo->id, $text, 'question_answer',
			"{'command':'CHAT', 'data':{'id':'{$request->person->id}'}}",
			'chatNewMessageHandler',
			['fromUser' => $request->person->id, 'message' => $message, 'image' => $fileName],
		);

		// complete challenge
		Challenges::complete("chat", $request->person->id);

		$response->setContent([
			'error' => false,
			'id' => $newMessageId
		]);
	}
}
