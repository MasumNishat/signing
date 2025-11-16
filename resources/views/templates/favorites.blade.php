<x-layout.app title="Favorite Templates">
    <div x-data="{
        templates: [],
        loading: true,
        async init() {
            await this.loadFavorites();
        },
        async loadFavorites() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/templates/favorites`);
                this.templates = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load favorite templates');
            } finally {
                this.loading = false;
            }
        },
        async removeFavorite(templateId) {
            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/templates/${templateId}/favorite`);
                $store.toast.success('Removed from favorites');
                await this.loadFavorites();
            } catch (error) {
                $store.toast.error('Failed to remove favorite');
            }
        },
        useTemplate(templateId) {
            window.location.href = `/templates/${templateId}/use`;
        }
    }"
    x-init="init()">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Favorite Templates</h1>
                <p class="mt-1 text-sm text-text-secondary">Quick access to your favorite templates</p>
            </div>
            <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                All Templates
            </x-ui.button>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="i in 6" :key="i">
                <x-ui.skeleton type="card" class="h-48" />
            </template>
        </div>

        <!-- Templates Grid -->
        <div x-show="!loading && templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="template in templates" :key="template.id">
                <x-ui.card class="hover:shadow-lg transition-shadow relative">
                    <!-- Favorite Star -->
                    <button
                        @click.stop="removeFavorite(template.id)"
                        class="absolute top-4 right-4 p-2 hover:bg-bg-hover rounded-full"
                    >
                        <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </button>

                    <!-- Template Content -->
                    <div @click="window.location.href=`/templates/${template.id}`" class="cursor-pointer">
                        <!-- Template Name -->
                        <h3 class="text-lg font-semibold text-text-primary mb-2" x-text="template.name"></h3>
                        <p class="text-sm text-text-secondary mb-4 line-clamp-2" x-text="template.description || 'No description'"></p>

                        <!-- Stats -->
                        <div class="flex items-center space-x-4 text-xs text-text-secondary mb-4">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="`${template.documents?.length || 0} docs`"></span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span x-text="`${template.recipients?.length || 0} roles`"></span>
                            </div>
                        </div>

                        <!-- Updated Date -->
                        <p class="text-xs text-text-secondary">
                            Updated: <span x-text="new Date(template.updated_at).toLocaleDateString()"></span>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 pt-4 border-t border-border-primary flex items-center justify-between">
                        <x-ui.button variant="secondary" size="sm" @click.stop="window.location.href=`/templates/${template.id}`">
                            View
                        </x-ui.button>
                        <x-ui.button variant="primary" size="sm" @click.stop="useTemplate(template.id)">
                            Use Template
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && templates.length === 0">
            <x-ui.card>
                <x-ui.empty-state
                    icon="star"
                    title="No favorite templates"
                    description="Mark templates as favorites for quick access"
                    action-text="Browse Templates"
                    action-url="/templates"
                />
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
