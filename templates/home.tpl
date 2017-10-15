<h1>Bienvenido al Chat</h1>

<p>Parece que nunca antes has chateado, usa el boton de abajo para escribir tu primera nota. Debes conocer el @username de la persona a quien quieres escribir. Para saber el @username puedes preguntarle o intentar buscarlo en la {link href="PIZARRA" caption="Pizarra"}.</p>

{space5}

<center>
	{button href="CHAT" desc="Escriba el @username de su amigo|Escriba el texto a enviar" caption="Primera Nota" popup="true" wait="false"}
	{button href="CHAT ONLINE" caption="Conectados" color="blue" wait="false"}
	{if $online == true}
	{button href="CHAT OCULTARSE" caption="Ocultarse" color="red" wait="false"}
	{else}
	{button href="CHAT MOSTRARSE" caption="Mostrarse" wait="false"}
	{/if}
</center>
