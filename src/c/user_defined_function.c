/* Unit 7: Functions
 * A user-defined function with a prototype, definition, and call -
 * computes the sum of two integers. */
#include <stdio.h>

int add(int a, int b); /* function prototype */

int main(void) {
    int x = 7, y = 8;
    printf("Sum = %d\n", add(x, y)); /* function call */
    return 0;
}

int add(int a, int b) { /* function definition */
    return a + b;
}
