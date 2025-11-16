<x-layout.app title="Users">
    <div x-data="{
        loading: true,
        users: [],
        async init() {
            await this.loadUsers();
        },
        async loadUsers() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/users`);
                this.users = response.data.data || response.data;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load users');
                this.loading = false;
            }
        }
    }"
    x-init="init()">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Users</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage team members and permissions</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/users/create'">
                Add User
            </x-ui.button>
        </div>

        <x-ui.card :padding="false">
            <div x-show="loading" class="p-6 space-y-4">
                <x-ui.skeleton type="text" class="h-12 w-full" />
                <x-ui.skeleton type="text" class="h-12 w-full" />
            </div>

            <div x-show="!loading">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead class="bg-bg-secondary">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-bg-primary divide-y divide-border-primary">
                        <template x-for="user in users" :key="user.id">
                            <tr class="hover:bg-bg-hover">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mr-3">
                                            <span class="font-semibold text-primary-600" x-text="user.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <span class="font-medium text-text-primary" x-text="user.name"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-text-secondary" x-text="user.email"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200"
                                          x-text="user.role || 'User'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                          :class="user.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800'"
                                          x-text="user.status || 'Active'"></span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="window.location.href=`/users/${user.id}`"
                                            class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                                        View
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</x-layout.app>
