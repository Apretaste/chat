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
	{button href="NOTA @{$friendUsername} Reemplace este texto por su nota" caption="Responder" body="" color="green" size="large"}
</center>
