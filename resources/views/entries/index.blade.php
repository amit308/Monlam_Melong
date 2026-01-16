<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('སྨོན་ལམ་མེ་ལོང་དྲི་བ་དྲིས་ལན་') }} (Monlam Melong Q&A)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium">{{ __('དྲི་བ་དྲིས་ལན་ཁག') }} (Questions and Answers)</h3>
                        </div>
                        @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                            <div>
                                <a href="{{ route('entries.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    {{ __('དྲི་བ་དང་ལན་གསར་པ།') }} (New Entry)
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Filters -->
                    <div class="mb-6">
                        @if($filters['hasFilters'])
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active Filters') }}:</span>
                                    @if($filters['category'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ __('Category') }}: {{ $categories->firstWhere('name', $filters['category'])->tibetan_name ?? $filters['category'] }}
                                        </span>
                                    @endif
                                    @if($filters['status'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ __('Status') }}: {{ __(ucfirst($filters['status'])) }}
                                        </span>
                                    @endif
                                    @if($filters['author'] && auth()->user()->isAdmin())
                                        @php
                                            $author = $authors->firstWhere('id', $filters['author']);
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ __('Author') }}: {{ $author->name ?? 'Unknown' }}
                                        </span>
                                    @endif
                                    @if(!empty($filters['question']))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                            {{ __('འཚོལ་') }}: {{ Str::limit($filters['question'], 40) }}
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('entries.index') }}" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    {{ __('Clear All') }}
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        <form action="{{ route('entries.index') }}" method="GET" class="flex items-end gap-4 flex-wrap">
                            <div class="min-w-[260px]">
                                <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('འཚོལ་') }} (Search)</label>
                                <input type="text" name="question" id="question" value="{{ request('question') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('འཚོལ་ཞིབ་བྱ་ཡུལ།') }} (Search)" />
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('སྡེ་ཚན།') }} (Category)</label>
                                <select name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">{{ __('སྡེ་ཚན་ཚང་མ།') }} (All Categories)</option>
                                    @foreach($categories as $category)
                                        @if($category)
                                            <option value="{{ $category->name }}" {{ request('category') == $category->name ? 'selected' : '' }}>{{ $category->tibetan_name ?: $category->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            @if(auth()->user()->isAdmin() || auth()->user()->isReviewer())
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('གནས་སྟངས།') }} (Status)</label>
                                    <select name="status" id="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">{{ __('གནས་སྟངས་ཚང་མ།') }} (All Statuses)</option>
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('ཟིན་བྲིས།') }} (Draft)</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('བསྐྱར་ཞིབ་ལ་བསྒུག་བཞིན་པ།') }} (Pending Review)</option>
                                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('ཆོག་མཆན་ཐོབ་པ།') }} (Approved)</option>
                                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('ངོས་ལེན་མ་བྱུང་བ།') }} (Rejected)</option>
                                    </select>
                                </div>
                            @endif

                            @if(auth()->user()->isAdmin())
                            <div>
                                <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('རྩོམ་སྒྲིག་མཁན།') }} (Author)</label>
                                <select name="author" id="author" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">{{ __('རྩོམ་སྒྲིག་མཁན་ཚང་མ།') }} (All Authors)</option>
                                    @foreach($authors as $author)
                                        <option value="{{ $author->id }}" {{ request('author') == $author->id ? 'selected' : '' }}>{{ $author->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="flex space-x-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    {{ __('འཚོལ།') }} (Filter)
                                </button>
                                @if($filters['hasFilters'])
                                <a href="{{ route('entries.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    {{ __('བཤེར་སྒྲིག་བཤིག་སྒྲོམ།') }} (Clear)
                                </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Entries table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('དྲི་བ།') }} (Question)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('སྡེ་ཚན།') }} (Category)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('དཀའ་ཚད།') }} (Difficulty)</th>
                                    @if(auth()->user()->isAdmin())
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('རྩོམ་སྒྲིག་མཁན།') }} (Author)</th>
                                    @endif
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('གནས་སྟངས།') }} (Status)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('ཐག་གཅོད།') }} (Actions)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($entries as $entry)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            <div class="truncate max-w-xs">{{ Str::limit($entry->question, 50) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $entry->category ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $entry->difficulty ?? '1' }}
                                        </td>
                                        @if(auth()->user()->isAdmin())
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $entry->user->name ?? 'Unknown' }}
                                            </td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @switch($entry->status)
                                                @case('draft')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        {{ __('ཟིན་བྲིས།') }} (Draft)
                                                    </span>
                                                    @break
                                                @case('pending')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {{ __('བསྐྱར་ཞིབ་ལ་བསྒུག་བཞིན་པ།') }} (Pending)
                                                    </span>
                                                    @break
                                                @case('approved')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        {{ __('ཆོག་མཆན་ཐོབ་པ།') }} (Approved)
                                                    </span>
                                                    @break
                                                @case('rejected')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        {{ __('ངོས་ལེན་མ་བྱུང་བ།') }} (Rejected)
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        {{ $entry->status }}
                                                    </span>
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 space-x-2">
                                            <a href="{{ route('entries.show', $entry) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('ལྟ་བ།') }}</a>

                                            @if(auth()->user()->isAdmin() || (auth()->id() == $entry->user_id && $entry->status === 'draft'))
                                                <a href="{{ route('entries.edit', $entry) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">{{ __('བཟོ་བཅོས།') }}</a>
                                            @endif

                                            @if($entry->status === 'draft' && (auth()->user()->isAdmin() || auth()->id() == $entry->user_id))
                                                <form action="{{ route('entries.submit-for-review', $entry) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">{{ __('བསྐྱར་ཞིབ་ལ་ཕུལ།') }}</button>
                                                </form>
                                            @endif

                                            @if(auth()->user()->isAdmin() || auth()->id() == $entry->user_id)
                                                <form action="{{ route('entries.destroy', $entry) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('གཏན་འཁེལ་ཡིན་ནམ?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">{{ __('འདོར།') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                            {{ __('དྲི་བ་དྲིས་ལན་མི་འདུག') }} (No entries found)
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $entries->appends(request()->query())->links() }}
                    </div>
                    
                    <script>
                        // This ensures that any dynamically added pagination links also maintain the filters
                        document.addEventListener('DOMContentLoaded', function() {
                            // Get all pagination links
                            const paginationLinks = document.querySelectorAll('.pagination a');
                            const currentUrl = new URL(window.location.href);
                            const searchParams = new URLSearchParams(currentUrl.search);
                            
                            // Add current filters to pagination links
                            paginationLinks.forEach(link => {
                                const linkUrl = new URL(link.href);
                                searchParams.forEach((value, key) => {
                                    if (key !== 'page') {
                                        linkUrl.searchParams.set(key, value);
                                    }
                                });
                                link.href = linkUrl.toString();
                            });
                        });

                        // Dynamic author filtering based on category selection
                        document.addEventListener('DOMContentLoaded', function() {
                            const categorySelect = document.getElementById('category');
                            const authorSelect = document.getElementById('author');
                            
                            if (categorySelect && authorSelect) {
                                // Store original authors data
                                const originalAuthors = Array.from(authorSelect.options).map(option => ({
                                    value: option.value,
                                    text: option.text,
                                    selected: option.selected
                                }));
                                
                                function updateAuthorDropdown(authors) {
                                    // Clear current author options
                                    authorSelect.innerHTML = '';
                                    
                                    // Add "All Authors" option
                                    const allOption = document.createElement('option');
                                    allOption.value = '';
                                    allOption.text = '{{ __("རྩོམ་སྒྲིག་མཁན་ཚང་མ།") }} ({{ __("All Authors") }})';
                                    authorSelect.appendChild(allOption);
                                    
                                    // Add authors
                                    authors.forEach(author => {
                                        const option = document.createElement('option');
                                        option.value = author.id;
                                        option.text = author.name;
                                        authorSelect.appendChild(option);
                                    });
                                }
                                
                                function restoreOriginalAuthors() {
                                    // Clear current author options
                                    authorSelect.innerHTML = '';
                                    
                                    // Restore original authors
                                    originalAuthors.forEach(author => {
                                        const option = document.createElement('option');
                                        option.value = author.value;
                                        option.text = author.text;
                                        option.selected = author.selected;
                                        authorSelect.appendChild(option);
                                    });
                                }
                                
                                categorySelect.addEventListener('change', function() {
                                    const selectedCategory = this.value;
                                    
                                    if (selectedCategory) {
                                        // Show loading state
                                        authorSelect.innerHTML = '<option value="">Loading...</option>';
                                        
                                        // Create a form to submit the request
                                        const form = document.createElement('form');
                                        form.method = 'GET';
                                        form.action = '{{ route("entries.index") }}';
                                        
                                        // Add category parameter
                                        const categoryInput = document.createElement('input');
                                        categoryInput.type = 'hidden';
                                        categoryInput.name = 'category';
                                        categoryInput.value = selectedCategory;
                                        form.appendChild(categoryInput);
                                        
                                        // Add ajax parameter
                                        const ajaxInput = document.createElement('input');
                                        ajaxInput.type = 'hidden';
                                        ajaxInput.name = 'ajax';
                                        ajaxInput.value = '1';
                                        form.appendChild(ajaxInput);
                                        
                                        // Submit the form
                                        document.body.appendChild(form);
                                        
                                        // Use fetch with the form data
                                        const formData = new FormData(form);
                                        const url = new URL(form.action);
                                        url.search = new URLSearchParams(formData).toString();
                                        
                                        fetch(url.toString(), {
                                            method: 'GET',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                            },
                                            credentials: 'same-origin'
                                        })
                                        .then(response => {
                                            if (!response.ok) {
                                                throw new Error(`HTTP error! status: ${response.status}`);
                                            }
                                            return response.json();
                                        })
                                        .then(data => {
                                            updateAuthorDropdown(data.authors);
                                        })
                                        .catch(error => {
                                            console.error('Error fetching authors:', error);
                                            // Fallback: restore original authors
                                            restoreOriginalAuthors();
                                        })
                                        .finally(() => {
                                            // Clean up the form
                                            document.body.removeChild(form);
                                        });
                                    } else {
                                        // No category selected, show all authors
                                        restoreOriginalAuthors();
                                    }
                                });
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
