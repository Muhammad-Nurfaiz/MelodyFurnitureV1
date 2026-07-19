@props([
    'name',

    'accept' => 'image/*',

    'preview' => null,

    'label' => 'Klik atau Drag gambar ke sini',

    'helper' => 'JPG, PNG, WEBP (Maksimal 2MB)',

    'multiple' => false,
])

@php

    $hasError = $errors->has(str_replace('[]', '', $name));

@endphp

<div

    x-data="{

        multiple:@js($multiple),

        drag:false,

        previews:[],

        filenames:[],

        init(){

            if(@js($preview)){

                if(this.multiple){

                    this.previews = @js($preview);

                }else{

                    this.previews = [@js($preview)];

                }

            }

        },

        updatePreview(event){

            this.clearObjectUrls();

            this.previews = [];

            this.filenames = [];

            [...event.target.files].forEach(file=>{

                this.previews.push(URL.createObjectURL(file));

                this.filenames.push(file.name);

            });

        },

        dropFile(event){

            this.drag=false;

            this.$refs.input.files = event.dataTransfer.files;

            this.updatePreview({

                target:this.$refs.input

            });

        },

        remove(index=null){

            this.clearObjectUrls();

            this.previews=[];

            this.filenames=[];

            this.$refs.input.value='';

        },

        clearObjectUrls(){

            this.previews.forEach(url=>{

                if(url.startsWith('blob:')){

                    URL.revokeObjectURL(url);

                }

            });

        }

    }"

    x-init="init()"

    x-on:beforeunload.window="clearObjectUrls()"

    class="space-y-4"

>

    {{-- Preview --}}

    <template x-if="previews.length">

        <div

            class="grid gap-4"

            :class="multiple
                ? 'grid-cols-2 md:grid-cols-4'
                : 'grid-cols-1'">

            <template

                x-for="(image,index) in previews"

                :key="index">

                <div

                    class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white">

                    <img

                        :src="image"

                        class="aspect-square w-full object-cover">

                    <button

                        type="button"

                        @click="remove(index)"

                        class="absolute right-2 top-2 hidden rounded-lg bg-white/90 p-1 shadow group-hover:block">

                        <x-heroicon-o-x-mark class="h-4 w-4"/>

                    </button>

                </div>

            </template>

        </div>

    </template>

    {{-- Upload Area --}}

    <label

        @dragover.prevent="drag=true"

        @dragleave="drag=false"

        @drop.prevent="dropFile($event)"

        :class="{

            'border-blue-500 bg-blue-50':drag,

            'border-red-400 bg-red-50':{{ $hasError ? 'true':'false' }},

            'border-gray-300 hover:border-blue-500 hover:bg-blue-50':!drag && !{{ $hasError ? 'true':'false' }}

        }"

        class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-8 py-10 transition-all duration-200">

        <input

            x-ref="input"

            type="file"

            name="{{ $name }}"

            accept="{{ $accept }}"

            @if($multiple)

                multiple

            @endif

            @change="updatePreview"

            {{ $attributes
                ->except([
                    'name',
                    'accept',
                    'multiple'
                ])
                ->merge([
                    'class'=>'hidden'
                ])
            }}

        >

        <div

            class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">

            <x-heroicon-o-photo

                class="h-8 w-8 text-blue-600"/>

        </div>

        <h4

            class="mt-5 text-base font-semibold text-gray-800">

            {{ $label }}

        </h4>

        <p

            class="mt-2 text-center text-sm text-gray-500">

            {{ $helper }}

        </p>

        <template x-if="filenames.length">

            <div

                class="mt-5 space-y-1 text-center">

                <template

                    x-for="file in filenames"

                    :key="file">

                    <p

                        class="text-xs font-medium text-blue-600"

                        x-text="file">

                    </p>

                </template>

            </div>

        </template>

    </label>

    {{-- Actions --}}

    <template x-if="previews.length">

        <div

            class="flex flex-wrap gap-3">

            <button

                type="button"

                @click="$refs.input.click()"

                class="rounded-xl border border-gray-300 px-4 py-2 text-sm transition hover:bg-gray-50">

                Ganti {{ $multiple ? 'Gambar' : 'Gambar' }}

            </button>

            <button

                type="button"

                @click="remove()"

                class="rounded-xl border border-red-200 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50">

                Hapus Semua

            </button>

        </div>

    </template>

    {{-- Validation --}}

    @if($hasError)

        <p

            class="text-sm text-red-600">

            {{ $errors->first(str_replace('[]', '', $name)) }}

        </p>

    @endif

</div>