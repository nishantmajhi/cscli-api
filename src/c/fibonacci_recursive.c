/* Unit 7: Functions
 * Nested/recursive function example: nth Fibonacci number. */
#include <stdio.h>

int fibonacci(int n) {
    if (n <= 1) {
        return n;
    }
    return fibonacci(n - 1) + fibonacci(n - 2);
}

int main(void) {
    int n;
    printf("Enter the term n: ");
    scanf("%d", &n);

    printf("Fibonacci(%d) = %d\n", n, fibonacci(n));
    return 0;
}
