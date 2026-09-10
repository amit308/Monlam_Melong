<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('དྲི་བ་དྲིས་ལན་ལྟ་བ།') }} (View Entry)
            </h2>
            <div>
                <a href="{{ $backUrl }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('ཕྱིར་ལོག') }} (Back)
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Entry metadata -->
                    <div class="flex justify-between mb-6">
                        <div class="space-y-2">
                            <div>
                                <span class="font-semibold">{{ __('སྡེ་ཚན།') }} (Category):</span>
                                <span class="ml-2">{{ $entry->category ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('དཀའ་ཚད།') }} (Difficulty):</span>
                                <span class="ml-2">{{ $entry->difficulty ?? '1' }}/5</span>
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('དོན་ཚན།') }} (Tags):</span>
                                <span class="ml-2">{{ $entry->tags ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('གནས་སྟངས།') }} (Status):</span>
                                <span class="ml-2">
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
                                </span>
                            </div>

                            <!-- Display feedback if available, especially for rejected entries -->
                            @if($entry->feedback)
                            <div class="mt-2">
                                <span class="font-semibold">{{ __('བསམ་འཆར།') }} (Feedback):</span>
                                <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                                    {{ $entry->feedback }}
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->isAdmin())
                                <div>
                                    <span class="font-semibold">{{ __('རྩོམ་སྒྲིག་མཁན།') }} (Author):</span>
                                    <span class="ml-2">{{ $entry->user?->name ?? 'Unknown' }}</span>
                                </div>
                            @endif

                            <div>
                                <span class="font-semibold">{{ __('བཟོ་བའི་དུས་ཚོད།') }} (Created):</span>
                                <span class="ml-2">{{ $entry->created_at->format('Y-m-d H:i') }}</span>
                            </div>

                            <div>
                                <span class="font-semibold">{{ __('བཟོ་བཅོས་བཏང་བའི་དུས་ཚོད།') }} (Updated):</span>
                                <span class="ml-2">{{ $entry->updated_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>

                        <div class="space-x-2">
                            @if(auth()->user()->isAdmin() || auth()->user()->isChiefEditor() || (auth()->id() == $entry->user_id && ($entry->status === 'draft' || $entry->status === 'pending' || $entry->status === 'rejected')))
                                <a href="{{ route('entries.edit', $entry) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('བཟོ་བཅོས།') }} (Edit)
                                </a>
                            @endif

                            @if(($entry->status === 'draft' || $entry->status === 'rejected') && (auth()->user()->isAdmin() || auth()->id() == $entry->user_id))
                                <a href="{{ route('entries.submit-via-get', $entry) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('བསྐྱར་ཞིབ་ལ་ཕུལ།') }} (Submit for Review)
                                </a>
                            @endif

                            @if($entry->status === 'pending' && auth()->user()->canReviewContent())
                                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <h4 class="font-semibold mb-2">{{ __('བསྐྱར་ཞིབ།') }} (Review)</h4>
                                    <form action="{{ route('entries.review', $entry) }}" method="POST">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block mb-2 text-sm font-medium">{{ __('གནས་སྟངས།') }} (Status)</label>
                                            <div class="flex space-x-4">
                                                <label class="inline-flex items-center">
                                                    <input type="radio" class="form-radio" name="status" value="approved" checked>
                                                    <span class="ml-2">{{ __('ཆོག་མཆན་སྤྲོད།') }} (Approve)</span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input type="radio" class="form-radio" name="status" value="rejected">
                                                    <span class="ml-2">{{ __('ངོས་ལེན་མི་བྱེད།') }} (Reject)</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="feedback" class="block mb-2 text-sm font-medium">{{ __('གཏམ་འཕྲིན།') }} (Feedback)</label>
                                            <textarea id="feedback" name="feedback" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200"></textarea>
                                        </div>
                                        <div>
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                {{ __('བསྐྱར་ཞིབ་ལན་སློག') }} (Submit Review)
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Entry content -->
                    <div class="mt-8 space-y-8">
                        <div>
                            <h3 class="text-xl font-bold mb-4">{{ __('རྒྱབ་ལྗོངས།') }} (Context)</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg whitespace-pre-wrap">
                                {{ $entry->context }}
                            </div>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-xl font-bold mb-4">{{ __('ཁུངས་ལུང་།') }} (Reference)</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg whitespace-pre-wrap">
                                {{ $entry->reference ?? __('ཁུངས་ལུང་མི་འདུག') }} (No reference provided)
                            </div>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-xl font-bold mb-4">{{ __('དྲི་བ།') }} (Question)</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg whitespace-pre-wrap">
                                {{ $entry->question }}
                            </div>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-xl font-bold mb-4">{{ __('ལན།') }} (Answer)</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg whitespace-pre-wrap">
                                {{ $entry->answer }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
