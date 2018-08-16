<h1>Charla con @{$friendUsername}</h1>
{if isset($online)}{if $online}&nbsp;<span class="online">ONLINE</span>{else}Ultima vez: {$last}{/if}{/if}
{space5}
{if not $chats}
	<p>Usted no ha chateado con @{$friendUsername} anteriormente. Presione el bot&oacute;n a continuaci&oacute;n para enviarle una primera nota.</p>
	<center>
	{button href="CHAT @{$friendUsername}" caption="Escribir" desc="a:Escriba el texto a enviar*" size="small" popup="true" wait="false"}
	{button href="CHAT" caption="Mis chats" size="small" color="grey"}
</center>
{else}
	<center>
		{button href="CHAT @{$friendUsername}" caption="Escribir" desc="a:Escriba el texto a enviar*" size="small" popup="true" wait="false"}
		{button href="CHAT" caption="Mis chats" size="small" color="grey"}
	</center>
	{space5}
	<table width="100%" cellspacing="0" cellpadding="5" border=0>
	{foreach item=item from=$chats}
		{assign var="color" value="black"}
		{if $item->gender eq "M"}{assign var="color" value="#4863A0"}{/if}
		{if $item->gender eq "F"}{assign var="color" value="#F778A1"}{/if}

		<tr {if $friendUsername == $item->username}bgcolor="#F2F2F2"{/if}>
			<!--PICTURE-->
			{if $APRETASTE_ENVIRONMENT eq "web"}
			<td width="1" valign="top">
				{img src="{$item->picture}" title="@{$item->username}" alt="@{$item->username}" class="profile-small"}
			</td>
			{/if}
			<td>
				<!--USERNAME AND DATE SENT-->
				<span style="font-size:10px;">
					{link href="PERFIL @{$item->username}" caption="@{$item->username}" style="color:{$color};"}
					<b>&middot;</b>
					<span style="color:grey;">{$item->sent|date_format:"%e/%m/%Y %I:%M %p"}</span>
				</span><br/>

				<!--TEXT-->
				<span style="color:{if $friendUsername == $item->username}#000000{else}#000066{/if};">
					{$item->text}{if $item->readed}<i onclick="alert('Leido el: {$item->read}')">&#10004;</i>{/if}
				</span>
			</td>
		</tr>
	{/foreach}
	</table>
{/if}

<style type="text/css">
{if $APRETASTE_ENVIRONMENT eq "web"}
	.profile-small{
		width:28px;
		height:28px;
		border-radius:100px;
	}
{/if}
	.online{
	background-color:#74C365;
	font-size:7px;
	padding:2px;
	border-radius:3px;
	color:white;
	font-weight:bold;
	}
</style>
