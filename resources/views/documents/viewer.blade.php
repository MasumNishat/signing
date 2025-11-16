<x-layout.app title="Document Viewer">
    <div x-data="{
        document: null,
        loading: true,
        currentPage: 1,
        totalPages: 1,
        zoom: 100,
        rotation: 0,
        async init() {
            await this.loadDocument();
        },
        async loadDocument() {
            this.loading = true;
            try {
                const documentId = window.location.pathname.split('/')[2];
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/documents/${documentId}`);
                this.document = response.data;

                // For PDF, we would normally load page count from PDF.js
                // For now, we'll use a placeholder
                this.totalPages = this.document.pages || 1;
            } catch (error) {
                $store.toast.error('Failed to load document');
                setTimeout(() => {
                    window.location.href = '/documents';
                }, 2000);
            } finally {
                this.loading = false;
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },
        zoomIn() {
            if (this.zoom < 200) {
                this.zoom += 25;
            }
        },
        zoomOut() {
            if (this.zoom > 50) {
                this.zoom -= 25;
            }
        },
        resetZoom() {
            this.zoom = 100;
        },
        rotateLeft() {
            this.rotation = (this.rotation - 90) % 360;
        },
        rotateRight() {
            this.rotation = (this.rotation + 90) % 360;
        },
        async downloadDocument() {
            try {
                const documentId = window.location.pathname.split('/')[2];
                window.location.href = `/api/v2.1/accounts/${$store.auth.user.account_id}/documents/${documentId}/download`;
            } catch (error) {
                $store.toast.error('Failed to download document');
            }
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
        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center h-screen">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
                <p class="mt-4 text-text-secondary">Loading document...</p>
            </div>
        </div>

        <!-- Document Viewer -->
        <div x-show="!loading" class="h-screen flex flex-col">
            <!-- Toolbar -->
            <div class="bg-bg-primary border-b border-border-primary px-4 py-3">
                <div class="flex items-center justify-between">
                    <!-- Left Side - Document Info -->
                    <div class="flex items-center space-x-4">
                        <x-ui.button variant="secondary" size="sm" onclick="window.location.href='/documents'">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back
                        </x-ui.button>
                        <div>
                            <h2 class="text-sm font-semibold text-text-primary" x-text="document?.name"></h2>
                            <p class="text-xs text-text-secondary" x-text="document ? formatFileSize(document.size) : ''"></p>
                        </div>
                    </div>

                    <!-- Center - Navigation & Zoom -->
                    <div class="flex items-center space-x-4">
                        <!-- Page Navigation -->
                        <div class="flex items-center space-x-2">
                            <button
                                @click="previousPage()"
                                :disabled="currentPage === 1"
                                class="p-2 rounded hover:bg-bg-hover disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <span class="text-sm text-text-primary">
                                Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                            </span>
                            <button
                                @click="nextPage()"
                                :disabled="currentPage === totalPages"
                                class="p-2 rounded hover:bg-bg-hover disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                        <div class="h-6 w-px bg-border-primary"></div>

                        <!-- Zoom Controls -->
                        <div class="flex items-center space-x-2">
                            <button
                                @click="zoomOut()"
                                :disabled="zoom === 50"
                                class="p-2 rounded hover:bg-bg-hover disabled:opacity-50"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                                </svg>
                            </button>
                            <button @click="resetZoom()" class="text-sm text-text-primary px-2 py-1 rounded hover:bg-bg-hover">
                                <span x-text="zoom"></span>%
                            </button>
                            <button
                                @click="zoomIn()"
                                :disabled="zoom === 200"
                                class="p-2 rounded hover:bg-bg-hover disabled:opacity-50"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                                </svg>
                            </button>
                        </div>

                        <div class="h-6 w-px bg-border-primary"></div>

                        <!-- Rotation Controls -->
                        <div class="flex items-center space-x-2">
                            <button @click="rotateLeft()" class="p-2 rounded hover:bg-bg-hover">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                            </button>
                            <button @click="rotateRight()" class="p-2 rounded hover:bg-bg-hover">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Right Side - Actions -->
                    <div class="flex items-center space-x-3">
                        <x-ui.button variant="secondary" size="sm" @click="downloadDocument()">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download
                        </x-ui.button>
                        <x-ui.button variant="primary" size="sm" onclick="window.print()">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print
                        </x-ui.button>
                    </div>
                </div>
            </div>

            <!-- Document Content Area -->
            <div class="flex-1 overflow-auto bg-gray-100 dark:bg-gray-900 p-8">
                <div class="max-w-5xl mx-auto">
                    <!-- Document Preview -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg mx-auto"
                         :style="`transform: scale(${zoom / 100}) rotate(${rotation}deg); transform-origin: center top;`">

                        <!-- For PDF Documents -->
                        <template x-if="document && document.mime_type === 'application/pdf'">
                            <div class="min-h-[1100px] p-12">
                                <div class="text-center py-24">
                                    <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">PDF Document</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2" x-text="document.name"></p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-4">
                                        Full PDF viewer requires PDF.js integration
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- For Image Documents -->
                        <template x-if="document && document.mime_type.startsWith('image/')">
                            <div class="p-8">
                                <img :src="`/api/v2.1/accounts/${$store.auth.user.account_id}/documents/${document.id}/content`"
                                     :alt="document.name"
                                     class="mx-auto max-w-full h-auto">
                            </div>
                        </template>

                        <!-- For Other Documents -->
                        <template x-if="document && !document.mime_type.startsWith('image/') && document.mime_type !== 'application/pdf'">
                            <div class="min-h-[1100px] p-12">
                                <div class="text-center py-24">
                                    <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300" x-text="document.mime_type.split('/')[1].toUpperCase() + ' Document'"></p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2" x-text="document.name"></p>
                                    <div class="mt-8">
                                        <x-ui.button variant="primary" @click="downloadDocument()">
                                            Download to View
                                        </x-ui.button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
