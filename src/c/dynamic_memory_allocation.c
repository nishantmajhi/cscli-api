/* Unit 9: Pointers
 * Dynamic memory allocation with malloc, calloc, realloc, and free. */
#include <stdio.h>
#include <stdlib.h>

int main(void) {
    int n;
    printf("How many integers? ");
    scanf("%d", &n);

    int *arr = (int *)malloc(n * sizeof(int)); /* allocate at runtime */
    if (arr == NULL) {
        printf("Memory allocation failed!\n");
        return 1;
    }

    printf("Enter %d integers: ", n);
    for (int i = 0; i < n; i++) {
        scanf("%d", &arr[i]);
    }

    int sum = 0;
    for (int i = 0; i < n; i++) {
        sum += arr[i];
    }
    printf("Sum = %d\n", sum);

    free(arr); /* release the allocated memory */
    return 0;
}
