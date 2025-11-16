<x-layout.app title="Contacts">
    <div x-data="{
        loading: true,
        contacts: [],
        showImport: false,
        importFile: null,
        async init() {
            await this.loadContacts();
        },
        async loadContacts() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}/contacts`);
                this.contacts = response.data;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load contacts');
                this.loading = false;
            }
        },
        async importContacts() {
            if (!this.importFile) {
                $store.toast.error('Please select a file');
                return;
            }

            const formData = new FormData();
            formData.append('file', this.importFile);

            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}/contacts/import`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                $store.toast.success('Contacts imported successfully');
                this.showImport = false;
                this.loadContacts();
            } catch (error) {
                $store.toast.error('Failed to import contacts');
            }
        },
        async deleteContact(id) {
            if (!confirm('Delete this contact?')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}/contacts/${id}`);
                $store.toast.success('Contact deleted');
                this.loadContacts();
            } catch (error) {
                $store.toast.error('Failed to delete contact');
            }
        }
    }"
    x-init="init()">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Contacts</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage your contact list</p>
            </div>
            <div class="flex space-x-3">
                <x-ui.button variant="secondary" @click="showImport = true">
                    Import Contacts
                </x-ui.button>
                <x-ui.button variant="primary" onclick="window.location.href='/contacts/create'">
                    Add Contact
                </x-ui.button>
            </div>
        </div>

        <!-- Import Modal -->
        <div x-show="showImport"
             x-transition
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showImport = false"></div>
                <x-ui.card class="relative z-10 max-w-md w-full">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Import Contacts</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-text-secondary mb-2">Upload File</label>
                            <input type="file"
                                   accept=".csv,.xlsx,.vcf"
                                   @change="importFile = $event.target.files[0]"
                                   class="w-full">
                            <p class="mt-1 text-xs text-text-secondary">Supported formats: CSV, XLSX, VCF</p>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <x-ui.button variant="secondary" @click="showImport = false">Cancel</x-ui.button>
                            <x-ui.button variant="primary" @click="importContacts()">Import</x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Contacts Grid -->
        <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-ui.skeleton type="card" class="h-40" />
            <x-ui.skeleton type="card" class="h-40" />
            <x-ui.skeleton type="card" class="h-40" />
        </div>

        <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="contact in contacts" :key="contact.id">
                <x-ui.card>
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                <span class="text-lg font-semibold text-primary-600" x-text="contact.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div>
                                <h3 class="font-medium text-text-primary" x-text="contact.name"></h3>
                                <p class="text-sm text-text-secondary" x-text="contact.email"></p>
                            </div>
                        </div>
                        <button @click="deleteContact(contact.id)" class="text-gray-400 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </x-ui.card>
            </template>
        </div>

        <div x-show="!loading && contacts.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-text-primary">No contacts</h3>
            <p class="mt-1 text-sm text-text-secondary">Get started by adding or importing contacts.</p>
        </div>
    </div>
</x-layout.app>
