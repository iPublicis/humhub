<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\admin\models\forms;

use humhub\modules\user\models\Group;
use Yii;
use humhub\libs\DynamicConfig;

/**
 * AuthenticationSettingsForm
 *
 * @since 0.5
 */
class AuthenticationSettingsForm extends \yii\base\Model
{
    public $internalAllowAnonymousRegistration;
    public $internalRequireApprovalAfterRegistration;
    public $internalUsersCanInvite;
    public $defaultUserGroup;
    public $defaultUserIdleTimeoutSec;
    public $allowGuestAccess;
    public $showCaptureInRegisterForm;
    public $defaultUserProfileVisibility;
    public $registrationApprovalMailContent;
    public $registrationDenialMailContent;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $settingsManager = Yii::$app->getModule('user')->settings;

        $this->internalUsersCanInvite = $settingsManager->get('auth.internalUsersCanInvite');
        $this->internalRequireApprovalAfterRegistration = $settingsManager->get('auth.needApproval');
        $this->internalAllowAnonymousRegistration = $settingsManager->get('auth.anonymousRegistration');
        $this->defaultUserGroup = $settingsManager->get('auth.defaultUserGroup');
        $this->defaultUserIdleTimeoutSec = $settingsManager->get('auth.defaultUserIdleTimeoutSec');
        $this->allowGuestAccess = $settingsManager->get('auth.allowGuestAccess');
        $this->showCaptureInRegisterForm = $settingsManager->get('auth.showCaptureInRegisterForm');
        $this->defaultUserProfileVisibility = $settingsManager->get('auth.defaultUserProfileVisibility');
        $this->registrationApprovalMailContent = $settingsManager->get('auth.registrationApprovalMailContent', ApproveUserForm::getDefaultApprovalMessage());
        $this->registrationDenialMailContent = $settingsManager->get('auth.registrationDenialMailContent', ApproveUserForm::getDefaultDeclineMessage());
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['internalUsersCanInvite', 'internalAllowAnonymousRegistration', 'internalRequireApprovalAfterRegistration', 'allowGuestAccess', 'showCaptureInRegisterForm'], 'boolean'],
            ['defaultUserGroup', 'exist', 'targetAttribute' => 'id', 'targetClass' => Group::class],
            ['defaultUserProfileVisibility', 'in', 'range' => [1, 2]],
            ['defaultUserIdleTimeoutSec', 'integer', 'min' => 20],
            [['registrationApprovalMailContent', 'registrationDenialMailContent'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'internalRequireApprovalAfterRegistration' => Yii::t('AdminModule.user', 'Require group admin approval after registration'),
            'internalAllowAnonymousRegistration' => Yii::t('AdminModule.user', 'Anonymous users can register'),
            'internalUsersCanInvite' => Yii::t('AdminModule.user', 'Members can invite external users by email'),
            'defaultUserGroup' => Yii::t('AdminModule.user', 'Default user group for new users'),
            'defaultUserIdleTimeoutSec' => Yii::t('AdminModule.user', 'Default user idle timeout, auto-logout (in seconds, optional)'),
            'allowGuestAccess' => Yii::t('AdminModule.user', 'Allow limited access for non-authenticated users (guests)'),
            'showCaptureInRegisterForm' => Yii::t('AdminModule.user', 'Include captcha in registration form'),
            'defaultUserProfileVisibility' => Yii::t('AdminModule.user', 'Default user profile visibility'),
            'registrationApprovalMailContent' => Yii::t('AdminModule.user', 'Default content of the registration approval email'),
            'registrationDenialMailContent' => Yii::t('AdminModule.user', 'Default content of the registration denial email'),
        ];
    }

    /**
     * Saves the form
     *
     * @return boolean
     */
    public function save()
    {
        $settingsManager = Yii::$app->getModule('user')->settings;

        $settingsManager->set('auth.internalUsersCanInvite', $this->internalUsersCanInvite);
        $settingsManager->set('auth.needApproval', $this->internalRequireApprovalAfterRegistration);
        $settingsManager->set('auth.anonymousRegistration', $this->internalAllowAnonymousRegistration);
        $settingsManager->set('auth.defaultUserGroup', $this->defaultUserGroup);
        $settingsManager->set('auth.defaultUserIdleTimeoutSec', $this->defaultUserIdleTimeoutSec);
        $settingsManager->set('auth.allowGuestAccess', $this->allowGuestAccess);

        if ($settingsManager->get('auth.allowGuestAccess')) {
            $settingsManager->set('auth.defaultUserProfileVisibility', $this->defaultUserProfileVisibility);
        }

        if ($settingsManager->get('auth.anonymousRegistration')) {
            $settingsManager->set('auth.showCaptureInRegisterForm', $this->showCaptureInRegisterForm);
        }

        if ($settingsManager->get('auth.needApproval')) {
            if (empty($this->registrationApprovalMailContent) || $this->registrationApprovalMailContent === ApproveUserForm::getDefaultApprovalMessage()) {
                $this->registrationApprovalMailContent = ApproveUserForm::getDefaultApprovalMessage();
                $settingsManager->delete('auth.registrationApprovalMailContent');
            } else {
                $settingsManager->set('auth.registrationApprovalMailContent', $this->registrationApprovalMailContent);
            }

            if (empty($this->registrationDenialMailContent) || $this->registrationDenialMailContent === ApproveUserForm::getDefaultDeclineMessage()) {
                $this->registrationDenialMailContent = ApproveUserForm::getDefaultDeclineMessage();
                $settingsManager->delete('auth.registrationDenialMailContent');
            } else {
                $settingsManager->set('auth.registrationDenialMailContent', $this->registrationDenialMailContent);
            }
        }

        DynamicConfig::rewrite();
        return true;
    }

}
