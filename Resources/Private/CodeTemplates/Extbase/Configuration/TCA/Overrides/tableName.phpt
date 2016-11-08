<?php
defined('TYPO3_MODE') || die();

<f:if condition="{addRecordTypeField}"><f:render partial="TCA/TypeField.phpt" arguments="{domainObject:rootDomainObject,databaseTableName:rootDomainObject.databaseTableName, extension:extension, settings:settings}" /></f:if>

<f:for each="{domainObjects}" as="domainObject"><f:if condition="{domainObject.mappedToExistingTable}">
<f:render partial="TCA/Columns.phpt" arguments="{domainObject:domainObject, settings:settings}" />
$GLOBALS['TCA']['{domainObject.databaseTableName}']['columns'][$GLOBALS['TCA']['{domainObject.databaseTableName}']['ctrl']['type']]['config']['items'][] = ['LLL:EXT:{domainObject.extension.extensionKey}/Resources/Private/Language/locallang_db.xlf:{domainObject.mapToTable}.tx_extbase_type.{domainObject.recordType}','{domainObject.recordType}'];
</f:if>
<f:if condition="{extension.supportLocalization}">$GLOBALS['TCA']['{domainObject.databaseTableName}']['columns']['sys_language_uid']['exclude'] = 0;
$GLOBALS['TCA']['{domainObject.databaseTableName}']['columns']['l10n_parent']['exclude'] = 0;</f:if>
<f:if condition="{domainObject.addHiddenField}">$GLOBALS['TCA']['{domainObject.databaseTableName}']['columns']['hidden']['exclude'] = 0;</f:if>
<f:if condition="{domainObject.addStarttimeEndtimeFields}">$GLOBALS['TCA']['{domainObject.databaseTableName}']['columns']['starttime']['exclude'] = 0;
$GLOBALS['TCA']['{domainObject.databaseTableName}']['columns']['endtime']['exclude'] = 0;</f:if>
</f:for>