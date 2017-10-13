<h1>Charla con @{$friendUsername}</h1>

<table width="100%" cellspacing="0" cellpadding="5" border=0>
{foreach item=item from=$chats}
	<tr><td {if $friendUsername == $item->username}bgcolor="#F2F2F2"{/if}>
		<span style="color: #AAAAAA;"><small>{$item->sent|date_format:"%e/%m/%Y %I:%M %p"}</small></span><br/>
		<b>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</b>:
		<span style="color:{if $friendUsername == $item->username}#000000{else}#000066{/if};">{$item->text}</span>
	</td></tr>
{/foreach}
</table>

{space30}

<center>
	{button href="CHAT @{$friendUsername}" caption="Responder" size="large" desc="Escriba el texto a enviar" popup="true" wait="false"}
	{if $online == true}
	{button href="CHAT OCULTARSE" caption="Ocultarse" color="red" wait="false"}
	{else}
	{button href="CHAT MOSTRARSE" caption="Mostrarse" wait="false"}
	{/if}
</center>
