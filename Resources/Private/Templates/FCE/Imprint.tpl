{extends file='Layouts/ContentWrap.tpl'}

{block name=content}
	{$resourceFactory = call_user_func(['TYPO3\\CMS\\Core\\Resource\\ResourceFactory', 'getInstance'])}
	{$file = 'EXT:vierwd_base/Resources/Public/Images/forward-media-logo.png'}
	{if $data.layout && in_array('imprint-dark', $data.layout)}
		{$file = 'EXT:vierwd_base/Resources/Public/Images/forward-media-logo--black.png'}
	{else if $data.layout && in_array('imprint-light', $data.layout)}
		{$file = 'EXT:vierwd_base/Resources/Public/Images/forward-media-logo--white.png'}
	{/if}
	{$logo = $resourceFactory->retrieveFileOrFolderObject($file)}

	{$header = $data.header|default:'Konzeption / Gestaltung / Realisierung'}
	{$subline = 'Digital Media / Corporate Design'}

	{if $TSFE->lang === 'de'}
		{$subline = 'Digitale Medien / Corporate Design'}
	{/if}

	{$baseData = [header => $header, layout => []]}
	{$data = $baseData + $cObj->data}

	{include 'Partials/Header.tpl' data=$data}

	<a href="https://www.4wdmedia.de" rel="noopener" target="_blank">
		<img src="{$logo->getPublicUrl()}" alt="FORWARD MEDIA" width="{$logo->getProperty('width') / 2}" style="margin-bottom: 15px">
	</a>

	{capture assign='bodytext'}
	<p>
		FORWARD MEDIA<br>
		{$subline}<br>
		<a class="external-link-new-window" href="https://www.4wdmedia.de">www.4wdmedia.de</a>
	</p>
	{/capture}

	{$cObj->parseFunc($bodytext, [], '< lib.parseFunc_RTE') nofilter}
{/block}
