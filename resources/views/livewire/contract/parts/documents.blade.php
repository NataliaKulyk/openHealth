<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{ __('forms.uploading_documents') }}</h2>
    </legend>

    <div>
        <p class="default-p mb-6">{{ __('contracts.statute_md5_info') }}</p>

        <div class="flex flex-col gap-3">
            <input id="statuteMd5"
                   type="file"
                   wire:model="form.statuteMd5"
                   class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <p class="text-xs text-gray-500">
                {{ __('forms.max_file_size_and_format') }}
            </p>

            @error('form.statuteMd5') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <p class="default-p mb-6">{{ __('contracts.additional_document_md5_info') }}</p>

        <div class="flex flex-col gap-3">
            <input id="additionalDocumentMd5"
                   type="file"
                   wire:model="form.additionalDocumentMd5"
                   class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <p class="text-xs text-gray-500">
                {{ __('forms.max_file_size_and_format') }}
            </p>

            @error('form.additionalDocumentMd5') <p class="text-error">{{ $message }}</p>@enderror
        </div>
    </div>
</fieldset>
