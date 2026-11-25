<div>
    @include('partials.note-mind-map', ['map' => \App\Support\MindMap::sanitize($this->note->mind_map), 'mode' => 'live'])
</div>
