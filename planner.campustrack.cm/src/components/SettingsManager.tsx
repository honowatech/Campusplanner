import React, { useState } from 'react';
import { AppSettings, User } from '@/src/lib/types';
import { useTranslation } from '@/src/utils/i18n';
import { Save, AlertCircle, Sliders, ShieldCheck, Key } from 'lucide-react';

interface SettingsManagerProps {
  settings: AppSettings;
  user: User;
  onUpdateSettings: (settings: AppSettings) => void;
}

export const SettingsManager: React.FC<SettingsManagerProps> = ({ settings, onUpdateSettings }) => {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState<'rules' | 'general' | 'api'>('rules');
  const [localSettings, setLocalSettings] = useState<AppSettings>(settings);
  const [showSuccess, setShowSuccess] = useState(false);

  const handleSave = () => {
    onUpdateSettings(localSettings);
    setShowSuccess(true);
    setTimeout(() => setShowSuccess(false), 3000);
  };

  const handleChange = (key: keyof AppSettings, value: string | number | boolean) => {
    setLocalSettings((prev) => ({ ...prev, [key]: value }));
  };

  const handleNumberChange = (key: keyof AppSettings, value: string) => {
    const numValue = parseInt(value) || 0;
    handleChange(key, numValue);
  };

  const handleSmsConfigChange = (key: keyof AppSettings['smsConfig'], value: string | number) => {
    setLocalSettings((prev) => ({
      ...prev,
      smsConfig: {
        ...prev.smsConfig,
        [key]: value,
      },
    }));
  };

  const ToggleSwitch = ({
    label,
    checked,
    onChange,
  }: {
    label: string;
    checked: boolean;
    onChange: () => void;
  }) => (
    <div className="flex items-center justify-between p-4 rounded-lg border border-gray-100 bg-gray-50 hover:border-indigo-100 transition-colors">
      <span className="text-sm font-semibold text-gray-700">{label}</span>
      <button
        onClick={onChange}
        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${checked ? 'bg-secondary' : 'bg-gray-200'}`}
      >
        <span
          className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${checked ? 'translate-x-6' : 'translate-x-1'}`}
        />
      </button>
    </div>
  );

  return (
    <div className="max-w-5xl mx-auto">
      <div className="flex items-center mb-8">
        <div className="p-3 bg-gray-100 rounded-xl mr-4">
          <Sliders className="text-gray-600" size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('settings')}</h1>
          <p className="text-gray-500 text-sm">{t('rulesDescription')}</p>
        </div>
      </div>

      <div className="flex flex-col lg:flex-row gap-8">
        {/* Sidebar / Tabs */}
        <div className="w-full lg:w-64 shrink-0">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <button
              onClick={() => setActiveTab('rules')}
              className={`w-full flex items-center space-x-3 px-4 py-4 text-sm font-medium transition-colors border-l-4 ${
                activeTab === 'rules'
                  ? 'bg-indigo-50 text-primary border-secondary'
                  : 'text-gray-600 hover:bg-gray-50 border-transparent'
              }`}
            >
              <ShieldCheck size={18} />
              <span>{t('managementRules')}</span>
            </button>
            <button
              onClick={() => setActiveTab('general')}
              className={`w-full flex items-center space-x-3 px-4 py-4 text-sm font-medium transition-colors border-l-4 ${
                activeTab === 'general'
                  ? 'bg-indigo-50 text-primary border-secondary'
                  : 'text-gray-600 hover:bg-gray-50 border-transparent'
              }`}
            >
              <Sliders size={18} />
              <span>{t('generalSettings')}</span>
            </button>
            <button
              onClick={() => setActiveTab('api')}
              className={`w-full flex items-center space-x-3 px-4 py-4 text-sm font-medium transition-colors border-l-4 ${
                activeTab === 'api'
                  ? 'bg-indigo-50 text-primary border-secondary'
                  : 'text-gray-600 hover:bg-gray-50 border-transparent'
              }`}
            >
              <Key size={18} />
              <span>{t('apiKeys')}</span>
            </button>
          </div>
        </div>

        {/* Content Area */}
        <div className="flex-1">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            {activeTab === 'rules' && (
              <div className="space-y-8">
                {/* Workload Limits */}
                <div>
                  <h3 className="text-lg font-bold text-gray-900 mb-1">{t('managementRules')}</h3>
                  <p className="text-sm text-gray-500 mb-6">{t('rulesDescription')}</p>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Max Hours Per Week */}
                    <div className="p-4 rounded-lg border border-gray-100 bg-gray-50 hover:border-indigo-100 transition-colors">
                      <label className="block text-sm font-semibold text-gray-700 mb-2">
                        {t('maxWeeklyHours')}
                      </label>
                      <input
                        type="number"
                        min="0"
                        max="100"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-gray-900"
                        value={localSettings?.maxWeeklyHoursPerTeacher}
                        onChange={(e) =>
                          handleNumberChange('maxWeeklyHoursPerTeacher', e.target.value)
                        }
                      />
                      <p className="text-xs text-gray-500 mt-2">
                        Maximum teaching load per teacher per week.
                      </p>
                    </div>

                    {/* Max Consecutive Hours */}
                    <div className="p-4 rounded-lg border border-gray-100 bg-gray-50 hover:border-indigo-100 transition-colors">
                      <label className="block text-sm font-semibold text-gray-700 mb-2">
                        {t('maxConsecutiveHours')}
                      </label>
                      <input
                        type="number"
                        min="0"
                        max="10"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-gray-900"
                        value={localSettings?.maxConsecutiveHours}
                        onChange={(e) => handleNumberChange('maxConsecutiveHours', e.target.value)}
                      />
                      <p className="text-xs text-gray-500 mt-2">
                        Limit on back-to-back sessions without a break.
                      </p>
                    </div>

                    {/* Max Daily Hours (Teacher) */}
                    <div className="p-4 rounded-lg border border-gray-100 bg-gray-50 hover:border-indigo-100 transition-colors">
                      <label className="block text-sm font-semibold text-gray-700 mb-2">
                        {t('maxDailyHoursTeacher')}
                      </label>
                      <input
                        type="number"
                        min="0"
                        max="12"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-gray-900"
                        value={localSettings?.maxDailyHoursPerTeacher}
                        onChange={(e) =>
                          handleNumberChange('maxDailyHoursPerTeacher', e.target.value)
                        }
                      />
                    </div>

                    {/* Max Daily Hours (Class) */}
                    <div className="p-4 rounded-lg border border-gray-100 bg-gray-50 hover:border-indigo-100 transition-colors">
                      <label className="block text-sm font-semibold text-gray-700 mb-2">
                        {t('maxDailyHoursClass')}
                      </label>
                      <input
                        type="number"
                        min="0"
                        max="12"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-gray-900"
                        value={localSettings?.maxDailyHoursPerClass}
                        onChange={(e) =>
                          handleNumberChange('maxDailyHoursPerClass', e.target.value)
                        }
                      />
                    </div>
                  </div>
                </div>

                {/* Conflict Constraints */}
                <div className="pt-6 border-t border-gray-100">
                  <h3 className="text-lg font-bold text-gray-900 mb-1">
                    {t('constraintSettings')}
                  </h3>
                  <p className="text-sm text-gray-500 mb-6">{t('constraintDescription')}</p>

                  <div className="space-y-4">
                    <ToggleSwitch
                      label={t('enableRoomConflict')}
                      checked={localSettings?.enableRoomConflict}
                      onChange={() =>
                        handleChange('enableRoomConflict', !localSettings?.enableRoomConflict)
                      }
                    />
                    <ToggleSwitch
                      label={t('enableTeacherConflict')}
                      checked={localSettings?.enableTeacherConflict}
                      onChange={() =>
                        handleChange('enableTeacherConflict', !localSettings?.enableTeacherConflict)
                      }
                    />
                    <ToggleSwitch
                      label={t('enableGroupConflict')}
                      checked={localSettings?.enableGroupConflict}
                      onChange={() =>
                        handleChange('enableGroupConflict', !localSettings?.enableGroupConflict)
                      }
                    />
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'general' && (
              <div className="text-center py-12 text-gray-500">
                <Sliders className="mx-auto mb-3 opacity-20" size={48} />
                <p>General application settings will appear here.</p>
              </div>
            )}

            {activeTab === 'api' && (
              <div className="space-y-8 animate-in fade-in duration-300">
                <div>
                  <div className="flex items-center space-x-3 mb-6">
                    <div className="p-2 bg-orange-100 text-orange-600 rounded-lg">
                      <Key size={24} />
                    </div>
                    <h3 className="text-lg font-bold text-gray-900">{t('smsNexah')}</h3>
                  </div>

                  <div className="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-6">
                    <div>
                      <label className="block text-sm font-semibold text-gray-700 mb-2">
                        {t('apiKey')}
                      </label>
                      <div className="relative">
                        <input
                          type="text"
                          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none font-mono text-sm bg-white"
                          placeholder="Enter your API Key"
                          value={localSettings?.smsConfig?.apiKey || ''}
                          onChange={(e) => handleSmsConfigChange('apiKey', e.target.value)}
                        />
                        <div className="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-400">
                          AES-256 Encrypted
                        </div>
                      </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                          {t('senderId')}
                        </label>
                        <input
                          type="text"
                          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none font-medium bg-white"
                          placeholder="CAMPUS PLANNER"
                          maxLength={11}
                          value={localSettings?.smsConfig?.sender_id || ''}
                          onChange={(e) => handleSmsConfigChange('sender_id', e.target.value)}
                        />
                        <p className="text-xs text-gray-500 mt-1">Max 11 characters</p>
                      </div>

                      <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                          {t('smsBalance')}
                        </label>
                        <div className="relative">
                          <input
                            type="number"
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-gray-900 bg-white"
                            value={localSettings?.smsConfig?.balance || 0}
                            onChange={(e) =>
                              handleSmsConfigChange('balance', parseInt(e.target.value) || 0)
                            }
                          />
                          <div className="absolute right-3 top-1/2 transform -translate-y-1/2 text-sm font-medium text-gray-500">
                            SMS
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Action Bar */}
            <div className="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
              <div className="flex items-center">
                {showSuccess && (
                  <div className="flex items-center text-green-600 text-sm font-medium animate-fade-in">
                    <AlertCircle size={16} className="mr-2" />
                    {t('settingsSaved')}
                  </div>
                )}
              </div>
              <button
                onClick={handleSave}
                className="flex items-center justify-center space-x-2 px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-secondary transition-colors shadow-sm font-medium"
              >
                <Save size={18} />
                <span>{t('save')}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
