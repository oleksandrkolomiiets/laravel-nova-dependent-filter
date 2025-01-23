***Examples***

**Type**

    class TypeFilter extends DependentFilter
    {
        public $component = 'dependent-filter';
    
        public $attribute = 'type';
    
        public $name = 'Type';
    
        public function options(Request $request, array $filters = []): array
        {
            return collect(TypeEnum::cases())->mapWithKeys(function ($case) {
                return [$case->value => $case->name()];
            })->toArray();
        }
    }

**Category (Dependent of Type)**

    class CategoryFilter extends DependentFilter
    {
        public $component = 'dependent-filter';
    
        public $attribute = 'category_id';
    
        public $dependentOf = ['type'];
    
        public $name = 'Category';
    
        public function options(Request $request, array $filters = []): array
        {
            if (! empty($filters['type'])) {
                return Category::query()
                    ->where('type', $filters['type'])
                    ->whereNull('parent_id')
                    ->pluck('title', 'id')
                    ->toArray();
            }
    
            return [];
        }
    }

**SubCategory (Dependent of Category)**

    class SubCategoryFilter extends DependentFilter
    {
        public $component = 'dependent-filter';
    
        public $attribute = 'sub_category_id';
    
        public $dependentOf = ['category_id'];
    
        public $name = 'Sub Category';
    
        public function options(Request $request, array $filters = []): array
        {
            if (! empty($filters['category_id'])) {
                return Category::query()
                    ->where('parent_id', $filters['category_id'])
                    ->pluck('title', 'id')
                    ->toArray();
            }
    
            return [];
        }
    }

***Usage***

    public function filters(NovaRequest $request): array
    {
        return [
            new TypeFilter,
            new CategoryFilter,
            new SubCategoryFilter,
        ];
    }
