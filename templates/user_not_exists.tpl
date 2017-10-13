<p>El usuario <b>@{$username}</b> no existe en Apretaste, por favor compruebe que el @username es v&aacute;lido. Puede que halla cometido un error al escribirlo, o que la persona halla cambiado su @username.</p>

{space10}

<center>
	{button href="CHAT amigo1 Hola amigo" desc="Escriba el @username seguido de la nota. Por ejemplo: @pepe Hola socio" caption="Enviar nota"}
	{if $online == true}
	{button href="CHAT OCULTARSE" caption="Ocultarse" color="red" wait="false"}
	{else}
	{button href="CHAT MOSTRARSE" caption="Mostrarse" wait="false"}
	{/if}
</center>
