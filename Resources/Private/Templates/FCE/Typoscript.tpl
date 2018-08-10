{extends file='Layouts/ContentWrap.tpl'}
{block name=content}
	{typoscript data=$cObj->data}
		{$cObj->data.bodytext nofilter}
	{/typoscript}
{/block}