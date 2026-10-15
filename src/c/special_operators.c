/* Unit 4: Operators and Expression
 * Special operators: sizeof and the comma operator. */
#include <stdio.h>

int main(void) {
    printf("sizeof(int)    = %zu\n", sizeof(int));
    printf("sizeof(double) = %zu\n", sizeof(double));

    int a, b, c;
    a = (b = 3, c = b + 2); /* comma operator: evaluates left to right */
    printf("a = %d, b = %d, c = %d\n", a, b, c);

    return 0;
}
