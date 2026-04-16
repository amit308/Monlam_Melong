<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('སྨོན་ལམ་རིག་ནུས་ཚད་འཇལ།') }}
            </h2>
            @if(Auth::check() && Auth::user()->canManageBenchmarks())
                <a href="{{ route('benchmark.create') }}" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded border-2 border-indigo-800 shadow-md">
                    {{ __('གསར་སྣོན།') }}
                </a>
            @endif

        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">

                    <!-- Filters -->
                    <div class="mb-6">
                        @if(($filters['hasFilters'] ?? false))
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active Filters') }}:</span>
                                    @if(($filters['subject'] ?? '') !== '')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ __('བརྗོད་གཞི།') }}: {{ $filters['subject'] }}
                                        </span>
                                    @endif
                                    @if(($filters['author'] ?? '') !== '')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ __('རྩོམ་སྒྲིག་མཁན།') }}: {{ $filters['author'] }}
                                        </span>
                                    @endif
                                    @if(($filters['date_from'] ?? '') !== '')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                            {{ __('ནས།') }} (From): {{ $filters['date_from'] }}
                                        </span>
                                    @endif
                                    @if(($filters['date_to'] ?? '') !== '')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                                            {{ __('བར།') }} (To): {{ $filters['date_to'] }}
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('benchmark.index') }}" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    {{ __('Clear All') }}
                                </a>
                            </div>
                        </div>
                        @endif

                        <form method="GET" action="{{ route('benchmark.index') }}" class="flex items-end gap-4 flex-wrap">
                             <div>
                                 <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('བརྗོད་གཞི།') }} (Subject)</label>
                                <select id="subject" name="subject" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">{{ __('བརྗོད་གཞི་ཚང་མ།') }} (All Subjects)</option>
                                    @foreach(($subjects ?? []) as $subject)
                                        <option value="{{ $subject }}" {{ ($filters['subject'] ?? '') === $subject ? 'selected' : '' }}>{{ $subject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('རྩོམ་སྒྲིག་མཁན།') }} (Author)</label>
                                <select id="author" name="author" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">{{ __('རྩོམ་སྒྲིག་མཁན་ཚང་མ།') }} (All Authors)</option>
                                    @foreach(($authors ?? []) as $authorItem)
                                        <option value="{{ $authorItem }}" {{ ($filters['author'] ?? '') === $authorItem ? 'selected' : '' }}>{{ $authorItem }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('ནས།') }} (From)</label>
                                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}" max="{{ now()->toDateString() }}" class="mt-1 block w-full min-w-[160px] px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 [&::-webkit-calendar-picker-indicator]:invert [&::-webkit-calendar-picker-indicator]:opacity-100" />
                            </div>
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('བར།') }} (To)</label>
                                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}" max="{{ now()->toDateString() }}" class="mt-1 block w-full min-w-[160px] px-3 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 [&::-webkit-calendar-picker-indicator]:invert [&::-webkit-calendar-picker-indicator]:opacity-100" />
                            </div>
                            <div class="flex space-x-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    {{ __('འཚོལ།') }} (Filter)
                                </button>
                                @if(($filters['hasFilters'] ?? false))
                                    <a href="{{ route('benchmark.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        {{ __('བཤེར་སྒྲིག་བཤིག་སྒྲོམ།') }} (Clear)
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    @if(session('success'))
                        <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($benchmarks->isEmpty())
                        <p class="text-center py-4 text-gray-600">{{ __('ཚད་འཇལ་གྱི་དྲི་བ་མི་འདུག') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-600 uppercase tracking-wider">
                                            {{ __('བརྗོད་གཞི།') }}
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-600 uppercase tracking-wider">
                                            {{ __('དྲི་བ།') }}
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-600 uppercase tracking-wider">
                                            {{ __('དཀའ་ཚད།') }}
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-600 uppercase tracking-wider">
                                            {{ __('རྩོམ་སྒྲིག་མཁན།') }} (Author)
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-600 uppercase tracking-wider">
                                            {{ __('གནས་སྟངས།') }}
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-center text-xs leading-4 font-medium text-gray-600 uppercase tracking-wider">
                                            {{ __('བྱེད་སྒོ།') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @foreach($benchmarks as $benchmark)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">
                                                <div class="text-sm leading-5 font-medium text-gray-900">
                                                    {{ $benchmark->subject }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 border-b border-gray-200">
                                                <div class="text-sm leading-5 text-gray-900 whitespace-normal">
                                                    {{ \Illuminate\Support\Str::limit($benchmark->question_text, 100) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    @if($benchmark->difficulty_level == 'easy') bg-emerald-100 text-emerald-800
                                                    @elseif($benchmark->difficulty_level == 'medium') bg-amber-100 text-amber-800
                                                    @elseif($benchmark->difficulty_level == 'hard') bg-orange-200 text-orange-900
                                                    @elseif($benchmark->difficulty_level == 'expert') bg-rose-100 text-rose-800
                                                    @endif">
                                                    {{ ucfirst($benchmark->difficulty_level) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $benchmark->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $benchmark->is_active ? __('འགྲོ་བཞིན་པ།') : __('འགྲོ་མེད་པ།') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">
                                                <div class="text-sm leading-5 text-gray-900">
                                                    {{ $benchmark->created_by ?? ($benchmark->user->name ?? 'Unknown') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200 text-center">
                                                <div class="flex justify-center space-x-2">
                                                    <a href="{{ route('benchmark.show', $benchmark->id) }}" class="text-blue-600 hover:text-blue-900 mr-2">
                                                        <i class="fas fa-eye"></i> {{ __('ལྟ་བ།') }}
                                                    </a>

                                                    @if(Auth::check() && Auth::user()->canManageBenchmarks())
                                                        <a href="{{ route('benchmark.edit', $benchmark->id) }}" class="text-green-600 hover:text-green-900 mr-2">
                                                            <i class="fas fa-edit"></i> {{ __('རྩོམ་སྒྲིག') }}
                                                        </a>

                                                        <form action="{{ route('benchmark.destroy', $benchmark->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('{{ __('ཁྱེད་ཀྱིས་དྲི་བ་འདི་གཙང་སེལ་བྱེད་རྒྱུ་གཏན་འཁེལ་ལམ?') }} / Are you sure you want to delete this question?');">
                                                                <i class="fas fa-trash"></i> {{ __('དོར་བ།') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $benchmarks->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
