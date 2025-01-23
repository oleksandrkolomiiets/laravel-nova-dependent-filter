<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <SelectControl
        :options="options"
        size="sm"
        class="w-full block"
        v-model="value"
        :dusk="filter.uniqueKey"
      >
        <option value="" :selected="value === ''">{{ __('&mdash;') }}</option>
      </SelectControl>
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce'


export default {
  emits: ['change'],

  props: {
    resourceName: {type: String, required: true},
    filterKey: {type: String, required: true},
    lens: String,
  },

  data: () => ({
    value: null,
    options: [],
    debouncedEventEmitter: null,
    loading: false,
  }),

  created() {
    this.debouncedEventEmitter = debounce(() => this.emitFilterChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.setCurrentFilterValue)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.setCurrentFilterValue)
  },

  watch: {
    value() {
      this.debouncedEventEmitter()
    },

    dependentFilters: {
      immediate: true,
      handler(newVal) {
        this.loading = true
        this.fetchOptions(newVal)
      }
    },
  },

  methods: {
    setCurrentFilterValue() {
      this.value = this.filter.currentValue
    },

    emitFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.value,
      })
    },

    async fetchOptions(filters) {
      const lens = this.lens ? `/lens/${this.lens}` : ''
      const {data: options} = await Nova.request().get(`/nova-api/${this.resourceName}${lens}/filters/options`, {
        params: {
          filters: btoa(JSON.stringify(filters)),
          filter: this.filterKey,
        },
      })

      this.options = options
      this.loading = false

      if (this.value !== '' && options.filter(option => option.value === this.value).length === 0 ) {
        this.value = ''
        this.emitFilterChange()
      }
    }
  },

  computed: {
    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
        this.filterKey
      )
    },

    dependentFilters() {
      return this.filter.dependentOf.reduce((acc, filterName) => {
        acc[filterName] = this.$store.getters[`${this.resourceName}/getFilter`](filterName).currentValue
        return acc
      }, {})
    },
  },
}
</script>
