<x-layout.app title="Documents">
    <div x-data="{
        documents: [],
        loading: true,
        viewMode: 'grid', // grid or list
        selectedDocuments: [],
        filters: {
            search: '',
            type: 'all', // all, pdf, word, excel, image
            sortBy: 'created_at',
            sortOrder: 'desc'
        },
        pagination: {
            current_page: 1,
            per_page: 24,
            total: 0
        },
        async init() {
            this.viewMode = localStorage.getItem('documents_view_mode') || 'grid';
            await this.loadDocuments();
        },
        async loadDocuments(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    search: this.filters.search,
                    type: this.filters.type,
                    sort_by: this.filters.sortBy,
                    sort_order: this.filters.sortOrder
                });

                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/documents?${params}`);
                this.documents = response.data.data || response.data;

                if (response.data.meta) {
                    this.pagination = {
                        current_page: response.data.meta.current_page,
                        per_page: response.data.meta.per_page,
                        total: response.data.meta.total
                    };
                }
            } catch (error) {
                $store.toast.error('Failed to load documents');
            } finally {
                this.loading = false;
            }
        },
        toggleViewMode() {
            this.viewMode = this.viewMode === 'grid' ? 'list' : 'grid';
            localStorage.setItem('documents_view_mode', this.viewMode);
        },
        toggleSelection(docId) {
            if (this.selectedDocuments.includes(docId)) {
                this.selectedDocuments = this.selectedDocuments.filter(id => id !== docId);
            } else {
                this.selectedDocuments.push(docId);
            }
        },
        selectAll() {
            if (this.selectedDocuments.length === this.documents.length) {
                this.selectedDocuments = [];
            } else {
                this.selectedDocuments = this.documents.map(doc => doc.id);
            }
        },
        async bulkDelete() {
            if (this.selectedDocuments.length === 0) return;
            if (!confirm(`Delete ${this.selectedDocuments.length} document(s)? This action cannot be undone.`)) return;

            try {
                await Promise.all(
                    this.selectedDocuments.map(id =>
                        $api.delete(`/accounts/${$store.auth.user.account_id}/documents/${id}`)
                    )
                );
                await this.loadDocuments();
                this.selectedDocuments = [];
                $store.toast.success('Documents deleted successfully');
            } catch (error) {
                $store.toast.error('Failed to delete documents');
            }
        },
        async deleteDocument(id) {
            if (!confirm('Delete this document? This action cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/documents/${id}`);
                await this.loadDocuments();
                $store.toast.success('Document deleted successfully');
            } catch (error) {
                $store.toast.error('Failed to delete document');
            }
        },
        getFileIcon(type) {
            const icons = {
                'application/pdf': '📄',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
                'application/msword': '📝',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
                'application/vnd.ms-excel': '📊',
                'image/png': '🖼️',
                'image/jpeg': '🖼️',
                'image/jpg': '🖼️',
                'image/gif': '🖼️'
            };
            return icons[type] || '📎';
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    }"
    x-init="init()">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Documents</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage and organize your documents</p>
            </div>
            <div class="flex items-center space-x-3">
                <x-ui.button variant="secondary" size="sm" @click="toggleViewMode()">
                    <span x-show="viewMode === 'grid'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </span>
                    <span x-show="viewMode === 'list'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </span>
                </x-ui.button>
                <x-ui.button variant="primary" onclick="window.location.href='/documents/upload'">
                    Upload Document
                </x-ui.button>
            </div>
        </div>

        <!-- Filters & Bulk Actions -->
        <x-ui.card class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[200px] max-w-md">
                    <x-ui.input
                        type="search"
                        placeholder="Search documents..."
                        x-model="filters.search"
                        @input.debounce.300ms="loadDocuments()"
                    />
                </div>

                <!-- Filters -->
                <div class="flex items-center space-x-3">
                    <x-ui.select x-model="filters.type" @change="loadDocuments()">
                        <option value="all">All Types</option>
                        <option value="pdf">PDF</option>
                        <option value="word">Word</option>
                        <option value="excel">Excel</option>
                        <option value="image">Images</option>
                    </x-ui.select>

                    <x-ui.select x-model="filters.sortBy" @change="loadDocuments()">
                        <option value="created_at">Upload Date</option>
                        <option value="name">Name</option>
                        <option value="size">Size</option>
                        <option value="type">Type</option>
                    </x-ui.select>

                    <button @click="filters.sortOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc'; loadDocuments()"
                            class="p-2 hover:bg-bg-hover rounded-md">
                        <svg x-show="filters.sortOrder === 'asc'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                        </svg>
                        <svg x-show="filters.sortOrder === 'desc'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div x-show="selectedDocuments.length > 0" class="mt-4 pt-4 border-t border-border-primary flex items-center justify-between">
                <span class="text-sm text-text-secondary">
                    <span x-text="selectedDocuments.length"></span> document(s) selected
                </span>
                <div class="flex items-center space-x-3">
                    <x-ui.button variant="secondary" size="sm" @click="selectedDocuments = []">
                        Deselect All
                    </x-ui.button>
                    <x-ui.button variant="danger" size="sm" @click="bulkDelete()">
                        Delete Selected
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="i in 8" :key="i">
                    <x-ui.skeleton type="card" class="h-48" />
                </template>
            </div>
            <div x-show="viewMode === 'list'" class="space-y-3">
                <template x-for="i in 6" :key="i">
                    <x-ui.skeleton type="text" class="h-16 w-full" />
                </template>
            </div>
        </div>

        <!-- Grid View -->
        <div x-show="!loading && viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="document in documents" :key="document.id">
                <x-ui.card class="hover:shadow-lg transition-shadow cursor-pointer relative" :padding="false">
                    <div class="absolute top-3 left-3 z-10">
                        <input type="checkbox"
                               :checked="selectedDocuments.includes(document.id)"
                               @change="toggleSelection(document.id)"
                               class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    </div>

                    <div @click="window.location.href=`/documents/${document.id}/viewer`" class="p-4">
                        <!-- Document Preview -->
                        <div class="h-32 bg-bg-secondary rounded-md flex items-center justify-center mb-3">
                            <span class="text-5xl" x-text="getFileIcon(document.mime_type)"></span>
                        </div>

                        <!-- Document Info -->
                        <div class="space-y-2">
                            <h3 class="font-semibold text-text-primary text-sm truncate" x-text="document.name"></h3>
                            <div class="flex items-center justify-between text-xs text-text-secondary">
                                <span x-text="formatFileSize(document.size)"></span>
                                <span x-text="new Date(document.created_at).toLocaleDateString()"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-4 py-3 bg-bg-secondary border-t border-border-primary flex items-center justify-between">
                        <a :href="`/documents/${document.id}/viewer`" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                            View
                        </a>
                        <div class="flex items-center space-x-3">
                            <a :href="`/documents/${document.id}/download`" class="text-text-secondary hover:text-text-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>
                            <button @click.stop="deleteDocument(document.id)" class="text-red-600 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </x-ui.card>
            </template>
        </div>

        <!-- List View -->
        <div x-show="!loading && viewMode === 'list'">
            <x-ui.card :padding="false">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead class="bg-bg-secondary">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left">
                                <input type="checkbox"
                                       @change="selectAll()"
                                       :checked="selectedDocuments.length === documents.length && documents.length > 0"
                                       class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Size</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Uploaded</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-bg-primary divide-y divide-border-primary">
                        <template x-for="document in documents" :key="document.id">
                            <tr class="hover:bg-bg-hover">
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                           :checked="selectedDocuments.includes(document.id)"
                                           @change="toggleSelection(document.id)"
                                           class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="mr-3 text-2xl" x-text="getFileIcon(document.mime_type)"></span>
                                        <span class="text-sm font-medium text-text-primary" x-text="document.name"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-text-secondary">
                                    <span x-text="document.mime_type.split('/')[1].toUpperCase()"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-text-secondary" x-text="formatFileSize(document.size)"></td>
                                <td class="px-6 py-4 text-sm text-text-secondary" x-text="new Date(document.created_at).toLocaleDateString()"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-3">
                                        <a :href="`/documents/${document.id}/viewer`" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                                            View
                                        </a>
                                        <a :href="`/documents/${document.id}/download`" class="text-text-secondary hover:text-text-primary">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                        </a>
                                        <button @click="deleteDocument(document.id)" class="text-red-600 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="documents.length === 0" class="px-6 py-12">
                    <x-ui.empty-state
                        icon="document"
                        title="No documents found"
                        description="Upload your first document to get started"
                        action-text="Upload Document"
                        action-url="/documents/upload"
                    />
                </div>
            </x-ui.card>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && pagination.total > pagination.per_page" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-text-secondary">
                Showing <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>
                to <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                of <span x-text="pagination.total"></span> documents
            </div>
            <div class="flex items-center space-x-2">
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    @click="loadDocuments(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1">
                    Previous
                </x-ui.button>
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    @click="loadDocuments(pagination.current_page + 1)"
                    :disabled="pagination.current_page * pagination.per_page >= pagination.total">
                    Next
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
