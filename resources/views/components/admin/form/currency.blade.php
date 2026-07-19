@props([
    'value' => '',
])

<div
    x-data="{

        raw:'{{ old($attributes->get('name'), $value) }}',

        display:'',

        init(){

            this.display = this.format(this.raw);

        },

        format(value){

            if(value === null) return '';

            value = value.toString().replace(/\D/g,'');

            return value.replace(/\B(?=(\d{3})+(?!\d))/g,'.');

        },

        input(e){

            this.raw = e.target.value.replace(/\D/g,'');

            this.display = this.format(this.raw);

        }

    }"

    class="relative">

    <span
        class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">

        Rp

    </span>

    <input
        type="hidden"
        name="{{ $attributes->get('name') }}"
        :value="raw">

    <input
        type="text"

        x-model="display"

        @input="input"

        class="
            w-full
            rounded-xl
            border
            border-gray-300
            bg-white
            py-3
            pl-12
            pr-4
            text-sm
            focus:border-blue-500
            focus:ring-2
            focus:ring-blue-100
        ">

</div>