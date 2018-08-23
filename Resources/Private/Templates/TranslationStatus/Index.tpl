{extends file='Layouts/Backend.tpl'}

{$getModuleUrl = ['TYPO3\\CMS\\Backend\\Utility\\BackendUtility', 'getModuleUrl']}

{block name=header}
<div class="module-docheader-bar t3js-module-docheader-bar">
	<div class="form-inline">
		<div class="form-group form-group-sm">
			<select class="form-control" name="_fileSelector" onchange="if(this.options[this.selectedIndex].value){ window.location.href=(this.options[this.selectedIndex].value);}">
				{if !$currentFileName}
					<option>Datei auswählen</option>
				{/if}
				{foreach $languageFiles as $extensionName => $files}
					<optgroup label="{$extensionName}">
						{foreach $files as $fileName}
							<option value="{uri_action arguments=[extensionName => $extensionName, fileName => $fileName]}"{if $extensionName === $currentExtensionName && $fileName === $currentFileName} selected{/if}>{$fileName}</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		</div>
	</div>
</div>
{/block}

{block name=content}
	<h1>Übersetzungsstatus</h1>
	<p>
		Gelb markierte Übersetzungen sind identisch mit Englisch. Möglicherweise sind diese unübersetzt.<br>Es kann allerdings auch sein, dass die Übersetzung gleich ist.
	</p>
	{if $translationKeys}
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Key</th>
					{foreach array_keys($translations) as $languageKey}
						<th>{$languageKey}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach $translationKeys as $translationKey}
					<tr>
						<th>{$translationKey}</th>
					{foreach $translations as $languageKey => $translationData}
						{if isset($translationData[$translationKey])}
							<td>{$translationData[$translationKey]}</td>
						{else}
							<td class="danger">
								<em>missing</em>
							</td>
						{/if}
					{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<p>Alle Labels sind in allen Sprachen übersetzt</p>
	{/if}
{/block}