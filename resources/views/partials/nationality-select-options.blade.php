@foreach (($countries ?? config('nationalities')) as $country)
    <option value="{{ $country }}" @selected((string) ($selected ?? '') === $country)>{{ \App\Support\Nationality::bilingualLabel($country) }}</option>
@endforeach
