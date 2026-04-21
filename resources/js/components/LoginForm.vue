<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Field,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  class?: HTMLAttributes["class"];
}>();

// Setup form with Inertia for login
const form = useForm({
  email: '',
  password: ''
});

// Handle form submission
const submit = () => {
  form.post('/admin/login', {
    onSuccess: () => {
      // Optional: redirect handled automatically by controller
    },
    onError: () => {
      // You could optionally focus the first field with an error
      if (form.errors.email) document.getElementById('email')?.focus();
      else if (form.errors.password) document.getElementById('password')?.focus();
    }
  });
};
</script>

<template>
  <div :class="cn('flex flex-col gap-6', props.class)">
    <Card>
      <CardHeader>
        <CardTitle>Login to your admin account</CardTitle>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit">
          <FieldGroup>
            <!-- Email Field -->
            <Field>
              <FieldLabel for="email">Email</FieldLabel>
              <Input
                id="email"
                type="email"
                placeholder="admin@example.com"
                required
                v-model="form.email"
                :aria-invalid="form.errors.email ? 'true' : 'false'"
              />
              <p v-if="form.errors.email" class="text-red-600 text-sm mt-1">
                {{ form.errors.email }}
              </p>
            </Field>

            <!-- Password Field -->
            <Field>
              <FieldLabel for="password">Password</FieldLabel>
              <Input
                id="password"
                type="password"
                required
                v-model="form.password"
                :aria-invalid="form.errors.password ? 'true' : 'false'"
              />
              <p v-if="form.errors.password" class="text-red-600 text-sm mt-1">
                {{ form.errors.password }}
              </p>
            </Field>

            <!-- Submit Button -->
            <Field>
              <Button type="submit" :disabled="form.processing">
                Login
              </Button>
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
