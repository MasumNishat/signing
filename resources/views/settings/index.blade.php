<x-layout.app title="Settings">
    <div x-data="{
        loading: true,
        settings: {},
        async init() {
            await this.loadSettings();
        },
        async loadSettings() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/settings`);
                this.settings = response.data;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load settings');
                this.loading = false;
            }
        },
        async saveSettings() {
            try {
                await $api.put(`/accounts/${$store.auth.user.account_id}/settings`, this.settings);
                $store.toast.success('Settings saved');
            } catch (error) {
                $store.toast.error('Failed to save settings');
            }
        }
    }"
    x-init="init()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Account Settings</h1>
            <p class="mt-1 text-sm text-text-secondary">Manage your account preferences and configuration</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Navigation -->
            <div class="lg:col-span-1">
                <x-ui.card>
                    <nav class="space-y-1">
                        <a href="#general" class="flex items-center px-3 py-2 text-sm font-medium rounded-md bg-primary-50 dark:bg-primary-900/20 text-primary-700">
                            General
                        </a>
                        <a href="#notifications" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-text-secondary hover:bg-bg-hover">
                            Notifications
                        </a>
                        <a href="#security" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-text-secondary hover:bg-bg-hover">
                            Security
                        </a>
                        <a href="#branding" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-text-secondary hover:bg-bg-hover">
                            Branding
                        </a>
                        <a href="#api" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-text-secondary hover:bg-bg-hover">
                            API Access
                        </a>
                    </nav>
                </x-ui.card>
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-3 space-y-6">
                <!-- General Settings -->
                <x-ui.card id="general">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">General Settings</h3>
                    <div class="space-y-4">
                        <x-form.input
                            label="Account Name"
                            x-model="settings.account_name"
                            placeholder="My Company"
                        />
                        <x-form.input
                            label="Default Language"
                            x-model="settings.default_language"
                            placeholder="en"
                        />
                        <x-form.input
                            label="Timezone"
                            x-model="settings.timezone"
                            placeholder="UTC"
                        />
                    </div>
                </x-ui.card>

                <!-- Notification Settings -->
                <x-ui.card id="notifications">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Notification Settings</h3>
                    <div class="space-y-4">
                        <x-form.checkbox
                            label="Email notifications for envelope events"
                            x-model="settings.email_notifications"
                        />
                        <x-form.checkbox
                            label="SMS notifications"
                            x-model="settings.sms_notifications"
                        />
                        <x-form.checkbox
                            label="Reminder notifications"
                            x-model="settings.reminder_notifications"
                        />
                    </div>
                </x-ui.card>

                <!-- Security Settings -->
                <x-ui.card id="security">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Security Settings</h3>
                    <div class="space-y-4">
                        <x-form.checkbox
                            label="Require two-factor authentication"
                            x-model="settings.require_2fa"
                        />
                        <x-form.input
                            label="Session Timeout (minutes)"
                            type="number"
                            x-model="settings.session_timeout"
                            placeholder="30"
                        />
                        <x-form.checkbox
                            label="Require strong passwords"
                            x-model="settings.require_strong_passwords"
                        />
                    </div>
                </x-ui.card>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <x-ui.button variant="primary" @click="saveSettings()">
                        Save Settings
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
