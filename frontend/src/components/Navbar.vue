<template>
  <nav class="bg-white shadow-md border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      
      <div>
        <h1 class="text-2xl font-bold text-blue-600">
          AMSAAS
        </h1>
      </div>

      <div class="flex items-center gap-4">
        <router-link
          to="/"
          class="text-gray-700 hover:text-blue-600 font-medium"
        >
          Home
        </router-link>

        <router-link
          to="/onboarding/company"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
        >
          Register Company
        </router-link>
      </div>
    </div>
  </nav>
</template>